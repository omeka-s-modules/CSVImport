<?php
namespace CSVImport\Controller;

use CSVImport\Form\ImportForm;
use CSVImport\Form\MappingForm;
use CSVImport\CsvFile;
use CSVImport\Job\Import;
use Omeka\Media\Ingester\Manager;
use Omeka\Settings\UserSettings;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Manager
     */
    protected $mediaIngesterManager;

    /**
     * @var UserSettings
     */
    protected $userSettings;

    /**
     * @param array $config
     * @param Manager $mediaIngesterManager
     * @param UserSettings $userSettings
     */
    public function __construct(array $config, Manager $mediaIngesterManager, UserSettings $userSettings)
    {
        $this->config = $config;
        $this->mediaIngesterManager = $mediaIngesterManager;
        $this->userSettings = $userSettings;
    }

    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(ImportForm::class);
        $view->form = $form;
        return $view;
    }

    public function mapAction()
    {
        $view = new ViewModel;
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin/csvimport');
        }

        $files = $request->getFiles()->toArray();
        $post = $this->params()->fromPost();
        $resourceType = $post['resource_type'];
        $delimiter = $this->getForm(ImportForm::class)->extractCsvOption($post['delimiter']);
        $enclosure = $this->getForm(ImportForm::class)->extractCsvOption($post['enclosure']);
        $automapCheckNamesAlone = (bool) $post['automap_check_names_alone'];
        $automapCheckUserList = (bool) $post['automap_check_user_list'];
        $automapUserList = $this->getForm(ImportForm::class)
            ->convertUserListTextToArray($post['automap_user_list']);
        $form = $this->getForm(MappingForm::class, [
            'resourceType' => $resourceType,
            'delimiter' => $post['delimiter'],
            'enclosure' => $post['enclosure'],
            'automap_check_names_alone' => $post['automap_check_names_alone'],
            'automap_check_user_list' => $post['automap_check_user_list'],
            'automap_user_list' => $post['automap_user_list'],
        ]);
        if (empty($files)) {
            $form->setData($post);
            if ($form->isValid()) {
                $args = $this->cleanArgs($post);
                $this->saveUserSettings($args);
                $dispatcher = $this->jobDispatcher();
                $job = $dispatcher->dispatch('CSVImport\Job\Import', $args);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
            }
            // TODO Set variables when the form is invalid.
            $this->messenger()->addError('Invalid settings.'); // @translate
            return $this->redirect()->toRoute('admin/csvimport');
        } else {
            $importForm = $this->getForm(ImportForm::class);
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $importForm->setData($post);
            if (!$importForm->isValid()) {
                $this->messenger()->addFormErrors($importForm);
                return $this->redirect()->toRoute('admin/csvimport');
            }

            $tmpFile = $post['csv']['tmp_name'];
            $csvFile = new CsvFile($this->config);
            $csvFile->setDelimiter($delimiter);
            $csvFile->setEnclosure($enclosure);
            $csvPath = $csvFile->getTempPath();
            $csvFile->moveToTemp($tmpFile);
            $csvFile->loadFromTempPath();

            $isUtf8 = $csvFile->isUtf8();
            if (! $csvFile->isUtf8()) {
                $this->messenger()->addError('File is not UTF-8 encoded.');
                return $this->redirect()->toRoute('admin/csvimport');
            }

            $columns = $csvFile->getHeaders();
            $view->setVariable('mediaForms', $this->getMediaForms());

            $config = $this->config;
            $automapOptions = [];
            $automapOptions['check_names_alone'] = $automapCheckNamesAlone;
            $automapOptions['format'] = 'form';
            if ($automapCheckUserList) {
                $automapOptions['automap_list'] = $automapUserList;
            }
            $autoMaps = $this->automapHeadersToMetadata($columns, $resourceType, $automapOptions);

            $mappingsResource = $this->orderMappingsForResource($resourceType);

            $view->setVariable('form', $form);
            $view->setVariable('automaps', $autoMaps);
            $view->setVariable('resourceType', $resourceType);
            $view->setVariable('mappings', $mappingsResource);
            $view->setVariable('columns', $columns);
            $view->setVariable('csvpath', $csvPath);
            $view->setVariable('filename', $post['csv']['name']);
            $view->setVariable('filesize', $post['csv']['size']);
        }
        return $view;
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $undoJobIds = [];
            foreach ($data['jobs'] as $jobId) {
                $undoJob = $this->undoJob($jobId);
                $undoJobIds[] = $undoJob->getId();
            }
            $this->messenger()->addSuccess('Undo in progress in the following jobs: ' . implode(', ', $undoJobIds));
        }
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + [
            'page' => $page,
            'sort_by' => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc'),
        ];
        $response = $this->api()->search('csvimport_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());
        return $view;
    }

    /**
     * Helper to order buttons on the mapping form for a resource type.
     *
     * For ergonomic reasons, it’s cleaner to keep the buttons of modules after
     * the default ones. This is only needed in the mapping form. The default
     * order is set in this module config too, before Zend merge.
     *
     * @param string $resourceType
     * @return array
     */
    protected function orderMappingsForResource($resourceType)
    {
        $config = include __DIR__ . '/../../config/module.config.php';
        $defaultOrder = $config['csv_import']['mappings'];
        $mappings = $this->config['csv_import']['mappings'];
        if (isset($defaultOrder[$resourceType])) {
            return array_values(array_unique(array_merge(
                $defaultOrder[$resourceType], $mappings[$resourceType]
            )));
        }
        return $mappings[$resourceType];
    }

    /**
     * Helper to clean posted args to get more readable logs.
     *
     * @todo Mix with check in Import and make it available for external query.
     *
     * @param array $post
     * @return array
     */
    protected function cleanArgs(array $post)
    {
        $args = $post;

        // Name of properties must be known to merge data and to process update.
        $api = $this->api();
        if (array_key_exists('column-property', $args)) {
            foreach ($args['column-property'] as $column => $ids) {
                $properties = [];
                foreach ($ids as $id) {
                    $term = $api->read('properties', $id)->getContent()->term();
                    $properties[$term] = $id;
                }
                $args['column-property'][$column] = $properties;
            }
        }

        if (!array_key_exists('column-multivalue', $post)) {
            $args['column-multivalue'] = [];
        }

        // Clean resource identifiers.
        if (array_key_exists('column-resource_identifier', $args)) {
            foreach ($args['column-resource_identifier'] as $column => $value) {
                $args['column-resource_identifier'][$column] = json_decode($value, true);
            }
        }
        unset($args['column-resource_identifier_property']);
        unset($args['column-resource_identifier_type']);

        // "unset()" allows to keep all csv parameters together in args.
        unset($args['delimiter']);
        unset($args['enclosure']);
        $args['delimiter'] = $this->getForm(ImportForm::class)->extractCsvOption($post['delimiter']);
        $args['enclosure'] = $this->getForm(ImportForm::class)->extractCsvOption($post['enclosure']);
        $args['escape'] = CsvFile::DEFAULT_ESCAPE;
        if (array_key_exists('multivalue_separator', $post)) {
            unset($args['multivalue_separator']);
            $args['multivalue_separator'] = $post['multivalue_separator'];
        }

        // Convert the user text into an array.
        if (array_key_exists('automap_user_list', $args)) {
            $args['automap_user_list'] = $this->getForm(ImportForm::class)
                ->convertUserListTextToArray($args['automap_user_list']);
        }

        // Set a default owner for a creation.
        if (empty($args['o:owner']['o:id']) && (empty($args['action']) || $args['action'] === Import::ACTION_CREATE)) {
            $args['o:owner'] = ['o:id' => $this->identity()->getId()];
        }

        return $args;
    }

    /**
     * Save user settings.
     *
     * @param array $settings
     */
    protected function saveUserSettings(array $settings)
    {
        foreach ($this->config['csv_import']['user_settings'] as $key => $value) {
            $name = substr($key, strlen('csv_import_'));
            if (isset($settings[$name])) {
                $this->userSettings()->set($key, $settings[$name]);
            }
        }
    }

    protected function getMediaForms()
    {
        $mediaIngester = $this->mediaIngesterManager;

        $forms = [];
        foreach ($mediaIngester->getRegisteredNames() as $ingester) {
            $forms[$ingester] = [
                'label' => $mediaIngester->get($ingester)->getLabel(),
            ];
        }
        return $forms;
    }

    protected function undoJob($jobId)
    {
        $response = $this->api()->search('csvimport_imports', ['job_id' => $jobId]);
        $csvImport = $response->getContent()[0];
        $dispatcher = $this->jobDispatcher();
        $job = $dispatcher->dispatch('CSVImport\Job\Undo', ['jobId' => $jobId]);
        $response = $this->api()->update('csvimport_imports',
            $csvImport->id(),
            [
                'o:undo_job' => ['o:id' => $job->getId() ],
            ]
        );
        return $job;
    }
}
