<?php
namespace CSVImport\Controller;

use CSVImport\Form\ImportForm;
use CSVImport\Form\MappingForm;
use CSVImport\CsvFile;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $mediaIngesterManager;

    protected $config;

    public function __construct(array $config, \Omeka\Media\Ingester\Manager $mediaIngesterManager)
    {
        $this->config = $config;
        $this->mediaIngesterManager = $mediaIngesterManager;
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
        $form = $this->getForm(MappingForm::class, ['resourceType' => $resourceType]);
        if (empty($files)) {
            $form->setData($post);
            if ($form->isValid()) {
                $dispatcher = $this->jobDispatcher();
                $job = $dispatcher->dispatch('CSVImport\Job\Import', $post);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
            }
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
            if ($resourceType == 'items' || $resourceType == 'item_sets') {
                $autoMaps = $this->getAutomaps($columns);
            } else {
                $autoMaps = [];
            }

            $mappingsResource = $this->orderMappingsForResource($resourceType);

            $view->setVariable('form', $form);
            $view->setVariable('automaps', $autoMaps);
            $view->setVariable('resourceType', $resourceType);
            $view->setVariable('mappings', $mappingsResource);
            $view->setVariable('columns', $columns);
            $view->setVariable('csvpath', $csvPath);
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
        $defaultOrder = [
            'items' => [
                '\CSVImport\Mapping\PropertyMapping',
                '\CSVImport\Mapping\ItemMapping',
                '\CSVImport\Mapping\MediaMapping',
            ],
            'users' => [
                '\CSVImport\Mapping\UserMapping',
            ],
        ];
        $mappings = $this->config['csv_import_mappings'];
        if (isset($defaultOrder[$resourceType])) {
            return array_values(array_unique(array_merge(
                $defaultOrder[$resourceType], $mappings[$resourceType]
            )));
        }
        return $mappings[$resourceType];
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

    protected function getAutomaps($columns)
    {
        $autoMaps = [];
        foreach ($columns as $index => $column) {
            $column = trim($column);
            if (preg_match('/^[a-z0-9-_]+:[a-z0-9-_]+$/i', $column)) {
                $response = $this->api()->search('properties', ['term' => $column]);
                $content = $response->getContent();
                if (! empty($content)) {
                    $property = $content[0];
                    $autoMaps[$index] = $property;
                }
            }
        }
        return $autoMaps;
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
