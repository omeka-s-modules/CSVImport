<?php
namespace CSVImport\Controller;

use CSVImport\Form\ImportForm;
use CSVImport\Form\MappingForm;
use CSVImport\Job\Import;
use CSVImport\Source\SourceInterface;
use finfo;
use Omeka\Media\Ingester\Manager;
use Omeka\Service\Exception\ConfigException;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var string
     */
    protected $tempPath;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Manager
     */
    protected $mediaIngesterManager;

    /**
     * @param array $config
     * @param Manager $mediaIngesterManager
     */
    public function __construct(array $config, Manager $mediaIngesterManager)
    {
        $this->config = $config;
        $this->mediaIngesterManager = $mediaIngesterManager;
    }

    public function indexAction()
    {
        $view = new ViewModel;

        /** @var \CSVImport\Form\ImportForm $form */
        $form = $this->getForm(ImportForm::class);
        $view->form = $form;

        /** @var \Zend\Session\Storage\SessionArrayStorage $session */
        $sessionManager = \Zend\Session\Container::getDefaultManager();
        $session = $sessionManager->getStorage();
        $session->clear('CSVImport');

        $user = $this->identity();
        /** @var \Omeka\Settings\UserSettings $userSettings */
        $userSettings = $this->userSettings();
        $userSettings->setTargetId($user->getId());

        $request = $this->getRequest();
        if (!$request->isPost()) {
            $data = [];
            $data['delimiter'] = $form->integrateParameter($userSettings->get('csvimport_delimiter',
                $this->config['csv_import']['user_settings']['csvimport_delimiter']));
            $data['enclosure'] = $form->integrateParameter($userSettings->get('csvimport_enclosure',
                $this->config['csv_import']['user_settings']['csvimport_enclosure']));
            $form->setData($data);
            return $view;
        }

        $post = array_merge_recursive(
            $request->getPost()->toArray(),
            $request->getFiles()->toArray()
        );
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $view;
        }

        // Advanced check of the form and the file.

        if (empty($post['source']['tmp_name'])) {
            $this->messenger()->addError('The file is not loaded.'); // @translate
            return $view;
        }

        $source = $this->getSource($post['source']);
        if (empty($source)) {
            $this->messenger()->addError('The format of the source cannot be detected.'); // @translate
            return $view;
        }

        $args = $form->getData();

        // TODO Why form remove resource_type?
        $resourceType = $post['resource_type'];
        $mediaType = $source->getMediaType();
        $args['media_type'] = $mediaType;
        $args = $this->cleanArgsImport($args);

        $tempPath = $this->getTempPath();
        $this->moveToTemp($args['source']['tmp_name']);

        $source->init($this->config);
        $source->setSource($tempPath);
        $source->setParameters($args);

        if (!$source->isValid()) {
            $message = $source->getErrorMessage() ?: 'The file is not valid.'; // @translate
            $this->messenger()->addError($message);
            return $view;
        }

        $columns = $source->getHeaders();
        if (empty($columns)) {
            $message = $source->getErrorMessage() ?: 'The file has no headers.'; // @translate
            $this->messenger()->addError($message);
            return $view;
        }

        // Prepare second step via session.

        $parameters = [];
        $parameters['filename'] = $args['source']['name'];
        $parameters['filesize'] = $args['source']['size'];
        $parameters['filepath'] = $tempPath;
        $parameters['media_type'] = $mediaType;
        $parameters['resource_type'] = $resourceType;
        if (isset($args['delimiter'])) {
            $parameters['delimiter'] = $args['delimiter'];
            $userSettings->set('csvimport_delimiter', $args['delimiter']);
        }
        if (isset($args['enclosure'])) {
            $parameters['enclosure'] = $args['enclosure'];
            $userSettings->set('csvimport_enclosure', $args['enclosure']);
        }
        if (isset($args['escape'])) {
            $parameters['escape'] = $args['escape'];
        }
        $parameters['columns'] = $columns;

        // TODO Set expiration hops.
        $session->offsetSet('CSVImport', ['parameters' => $parameters]);
        return $this->redirect()->toRoute(null, ['action' => 'map'], true);
    }

    public function mapAction()
    {
        /** @var \Zend\Session\Storage\SessionArrayStorage $session */
        $sessionManager = \Zend\Session\Container::getDefaultManager();
        $session = $sessionManager->getStorage();
        $csvImportSession = $session->offsetGet('CSVImport');
        if (empty($csvImportSession) || empty($csvImportSession['parameters'])) {
            $session->clear('CSVImport');
            $message = 'Fill the form below first.'; // @translate
            $this->messenger()->addError($message);
            return $this->redirect()->toRoute('admin/csvimport');
        }

        $parameters = $csvImportSession['parameters'];
        $resourceType = $parameters['resource_type'];
        $automapOptions = [];
        $automapOptions['automap_by_label'] = true;
        $automapOptions['format'] = 'form';
        $automapOptions['mappings'] = $this->config['csv_import']['mappings'][$resourceType];
        $autoMaps = $this->automapHeadersToMetadata($parameters['columns'], $resourceType, $automapOptions);

        $view = new ViewModel;

        /** @var \CSVImport\Form\MappingForm $form */
        $form = $this->getForm(MappingForm::class, ['resource_type' => $resourceType]);
        $view->setVariable('form', $form);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('columns', $parameters['columns']);
        $view->setVariable('automaps', $autoMaps);
        $view->setVariable('mappings', $this->getMappingsForResource($resourceType));
        $view->setVariable('mediaForms', $this->getMediaForms());

        $request = $this->getRequest();

        $user = $this->identity();
        /** @var \Omeka\Settings\UserSettings $userSettings */
        $userSettings = $this->userSettings();
        $userSettings->setTargetId($user->getId());

        // If this is not a request, this is the first call to the second step,
        // so fill the form with the user settings.
        if (!$request->isPost()) {
            $data = [];
            $data['advanced-settings']['identifier_property'] = $userSettings->get('csvimport_identifier_property',
                $this->config['csv_import']['user_settings']['csvimport_identifier_property']);
            $data['advanced-settings']['rows_by_batch'] = $userSettings->get('csvimport_rows_by_batch',
                $this->config['csv_import']['user_settings']['csvimport_rows_by_batch']);
            $data['multivalue_separator'] = $userSettings->get('csvimport_multivalue_separator',
                $this->config['csv_import']['user_settings']['csvimport_multivalue_separator']);
            $data['multivalue_by_default'] = $userSettings->get('csvimport_multivalue_by_default',
                $this->config['csv_import']['user_settings']['csvimport_multivalue_by_default']);
            $data['language'] = $userSettings->get('csvimport_language',
                $this->config['csv_import']['user_settings']['csvimport_language']);
            $data['language_by_default'] = $userSettings->get('csvimport_language_by_default',
                $this->config['csv_import']['user_settings']['csvimport_language_by_default']);
            $form->setData($data);
            return $view;
        }

        $post = $this->params()->fromPost();
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addError('Invalid settings.'); // @translate
            $this->messenger()->addFormErrors($form);
            return $view;
        }

        $session->clear('CSVImport');

        // TODO Check why getData() doesn't return all values?
        // $post = $form->getData();

        unset($parameters['columns']);
        $args = $parameters + $this->cleanArgs($post);

        $userSettings->set('csvimport_rows_by_batch', $args['advanced-settings']['rows_by_batch']);
        $userSettings->set('csvimport_identifier_property', $args['advanced-settings']['identifier_property']);
        $userSettings->set('csvimport_multivalue_separator', $args['multivalue_separator']);
        $userSettings->set('csvimport_multivalue_by_default', (int) (bool) $post['multivalue_by_default']);
        $userSettings->set('csvimport_language', $args['language']);
        $userSettings->set('csvimport_language_by_default', (int) (bool) $post['language_by_default']);

        $dispatcher = $this->jobDispatcher();
        $job = $dispatcher->dispatch('CSVImport\Job\Import', $args);
        // The CsvImport record is created in the job, so it doesn't
        // happen until the job is done.
        $message = new Message(
            'Importing in background (%sjob #%d%s)', // @translate
            sprintf('<a href="%s">',
                htmlspecialchars($this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()]))
            ),
            $job->getId(),
            '</a>'
        );
        $message->setEscapeHtml(false);
        $this->messenger()->addSuccess($message);
        return $this->redirect()->toRoute('admin/csvimport', ['action' => 'past-imports'], true);
    }

    public function browseAction()
    {
        $this->forward('past-imports');
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
            $message = new Message(
                'Undo in progress in the following jobs: %s', // @translate
                implode(', ', $undoJobIds));
            $this->messenger()->addSuccess($message);
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
     * Get the source class to manage the file, according to its media type.
     *
     * @todo Use the class TempFile before.
     *
     * @param array $fileData File data from a post ($_FILES).
     * @return SourceInterface|null
     */
    protected function getSource(array $fileData)
    {
        if (empty($fileData['tmp_name'])) {
            return;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mediaType = $finfo->file($fileData['tmp_name']);

        // Manage an exception for a very common format, undetected by fileinfo.
        if ($mediaType === 'text/plain') {
            $extensions = [
                'csv' => 'text/csv',
                'tab' => 'text/tab-separated-values',
                'tsv' => 'text/tab-separated-values',
            ];
            $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            if (isset($extensions[$extension])) {
                $mediaType = $extensions[$extension];
            }
        }

        $sources = $this->config['csv_import']['sources'];
        if (!isset($sources[$mediaType])) {
            return;
        }

        $source = new $sources[$mediaType];
        return $source;
    }

    /**
     * Helper to return ordered mappings of the selected resource type.
     *
     * @param string $resourceType
     * @return array
     */
    protected function getMappingsForResource($resourceType)
    {
        // First reorder mappings: for ergonomic reasons, it’s cleaner to keep
        // the buttons of modules after the default ones. This is only needed in
        // the mapping form. The default order is set in this module config too,
        // before Zend merge.
        $config = include dirname(dirname(__DIR__)) . '/config/module.config.php';
        $defaultOrder = $config['csv_import']['mappings'];
        $mappings = $this->config['csv_import']['mappings'];
        if (isset($defaultOrder[$resourceType])) {
            $mappingClasses = array_values(array_unique(array_merge(
                $defaultOrder[$resourceType], $mappings[$resourceType]
            )));
        } else {
            $mappingClasses = $mappings[$resourceType];
        }
        $mappings = [];
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass();
        }
        return $mappings;
    }

    /**
     * Helper to clean posted args after import form.
     *
     * @todo Mix with check in Import and make it available for external query.
     *
     * @param array $post
     * @return array
     */
    protected function cleanArgsImport(array $post)
    {
        $args = $post;
        // TODO Move to the source class.
        unset($args['delimiter']);
        unset($args['enclosure']);
        switch ($post['media_type']) {
            case 'text/csv':
                $args['delimiter'] = $this->getForm(ImportForm::class)->extractParameter($post['delimiter']);
                $args['enclosure'] = $this->getForm(ImportForm::class)->extractParameter($post['enclosure']);
                $args['escape'] = \CSVImport\Source\CsvFile::DEFAULT_ESCAPE;
                break;
            case 'text/tab-separated-values':
                // Nothing to do.
                break;
        }
        return $args;
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

        // Set the default action if not set, for example for users.
        $args['action'] = empty($args['action'])
            ? \CSVImport\Job\Import::ACTION_CREATE
            : $args['action'];

        // Set values as integer.
        foreach (['o:resource_template', 'o:resource_class', 'o:owner', 'o:item'] as $meta) {
            if (!empty($args[$meta]['o:id'])) {
                $args[$meta] = ['o:id' => (int) $args[$meta]['o:id']];
            }
        }
        foreach (['o:is_public', 'o:is_open', 'o:is_active'] as $meta) {
            if (isset($args[$meta]) && strlen($args[$meta])) {
                $args[$meta] = (int) $args[$meta];
            }
        }

        // Set arguments as integer.
        if (!empty($args['rows_by_batch'])) {
            $args['rows_by_batch'] = (int) $args['rows_by_batch'];
        }

        // Set arguments as boolean.
        foreach (['automap_by_label', 'automap_check_user_list'] as $meta) {
            if (array_key_exists($meta, $args)) {
                $args[$meta] = (bool) $args[$meta];
            }
        }

        // Name of properties must be known to merge data and to process update.
        $api = $this->api();
        if (array_key_exists('column-property', $args)) {
            foreach ($args['column-property'] as $column => $ids) {
                $properties = [];
                foreach ($ids as $id) {
                    $term = $api->read('properties', $id)->getContent()->term();
                    $properties[$term] = (int) $id;
                }
                $args['column-property'][$column] = $properties;
            }
        }

        // Check the identifier property.
        if (array_key_exists('identifier_property', $args)) {
            $identifierProperty = $args['identifier_property'];
            if (empty($identifierProperty) && $identifierProperty !== 'internal_id') {
                $properties = $api->search('properties', ['term' => $identifierProperty])->getContent();
                if (empty($properties)) {
                    $args['identifier_property'] = null;
                }
            }
        }

        if (!array_key_exists('column-multivalue', $post)) {
            $args['column-multivalue'] = [];
        }

        // Set default multivalue separator if not set, for example for users.
        if (!array_key_exists('multivalue_separator', $args)) {
            $args['multivalue_separator'] = ',';
        }

        // Set default automap by label if not set, for example for users.
        if (!array_key_exists('automap_by_label', $args)) {
            $args['automap_by_label'] = false;
        }

        // Set a default owner for a creation.
        if (empty($args['o:owner']['o:id']) && (empty($args['action']) || $args['action'] === Import::ACTION_CREATE)) {
            $args['o:owner'] = ['o:id' => $this->identity()->getId()];
        }

        // Remove useless input fields from sidebars.
        unset($args['csrf']);
        unset($args['automap_by_label']);
        unset($args['column-resource_property']);
        unset($args['column-item_set_property']);
        unset($args['column-item_property']);
        unset($args['column-media_property']);
        unset($args['multivalue_by_default']);
        unset($args['language_by_default']);
        unset($args['value-language']);
        unset($args['multivalue']);
        unset($args['resource-data']);
        unset($args['media-source']);
        unset($args['column-import']);

        return $args;
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
        ksort($forms);
        return $forms;
    }

    /**
     * Move a file to the temp path.
     *
     * @param string $systemTempPath
     */
    protected function moveToTemp($systemTempPath)
    {
        move_uploaded_file($systemTempPath, $this->getTempPath());
    }

    /**
     * Get the path to the temporary file.
     *
     * @param null|string $tempDir
     * @return string
     */
    protected function getTempPath($tempDir = null)
    {
        if (isset($this->tempPath)) {
            return $this->tempPath;
        }
        if (!isset($tempDir)) {
            $config = $this->config;
            if (!isset($config['temp_dir'])) {
                throw new ConfigException('Missing temporary directory configuration');
            }
            $tempDir = $config['temp_dir'];
        }
        $this->tempPath = tempnam($tempDir, 'omeka');
        return $this->tempPath;
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
