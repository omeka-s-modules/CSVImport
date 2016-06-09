<?php
namespace CSVImport\Controller;

use CSVImport\Form\ImportForm;
use CSVImport\Form\MappingForm;
use CSVImport\CsvFile;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
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
        $logger = $this->getServiceLocator()->get('Omeka\Logger');
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $files = $request->getFiles()->toArray();
            $post = $this->params()->fromPost();
            $resourceType = $post['resource_type'];
            $form = $this->getForm(MappingForm::class, array('resourceType' => $resourceType));
            if (empty($files)) {
                
                $form->setData($post);
                
                if ($form->isValid()) {
                    $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                    $job = $dispatcher->dispatch('CSVImport\Job\Import', $post);
                    //the Omeka2Import record is created in the job, so it doesn't
                    //happen until the job is done
                    $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                    return $this->redirect()->toRoute('admin/csvimport/past-imports', ['action' => 'browse'], true);
                }
            } else {
                $view->setVariable('form', $form);
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );

                $tmpFile = $post['csv']['tmp_name'];
                $csvFile = new CsvFile($this->getServiceLocator());
                $csvPath = $csvFile->getTempPath();
                $csvFile->moveToTemp($tmpFile);
                $csvFile->loadFromTempPath();
                
                $isUtf8 = $csvFile->isUtf8();
                if (! $csvFile->isUtf8()) {
                    $this->messenger()->addError('File is not UTF-8 encoded.');
                    return;
                }
                
                
                $columns = $csvFile->getHeaders();
                $view->setVariable('mediaForms', $this->getMediaForms());

                $config = $this->serviceLocator->get('Config');
                if($resourceType == 'items' || $resourceType == 'item_sets') {
                    $autoMaps = $this->getAutomaps($columns);
                } else {
                    $autoMaps = [];
                }
                
                $view->setVariable('automaps', $autoMaps);
                $view->setVariable('resourceType', $resourceType);
                $view->setVariable('mappings', $config['csv_import_mappings'][$resourceType]);
                $view->setVariable('columns', $columns);
                $view->setVariable('csvpath', $csvPath);
            }
        }
        return $view;
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            foreach ($data['jobs'] as $jobId) {
                $this->undoJob($jobId);
            }
        }
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page'       => $page,
            'sort_by'    => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc')
        );
        $response = $this->api()->search('csvimport_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());
        return $view;
    }

    protected function getMediaForms()
    {
        $services = $this->getServiceLocator();
        $mediaIngester = $services->get('Omeka\MediaIngesterManager');

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
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $autoMaps = [];
        foreach($columns as $index=>$column) {
            if (strpos($column, ':') !== false) {
                $response = $api->search('properties', array('term' => trim($column)));
                $content = $response->getContent();
                if (! empty($content)) {
                    $property = $content[0];
                    $autoMaps[$index] = $property;
                }
            }
        }
        return $autoMaps;
    }
    
    protected function undoJob($jobId) {
        $response = $this->api()->search('csvimport_imports', array('job_id' => $jobId));
        $csvImport = $response->getContent()[0];
        $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
        $job = $dispatcher->dispatch('CSVImport\Job\Undo', array('jobId' => $jobId));
        $response = $this->api()->update('csvimport_imports', 
                    $csvImport->id(), 
                    array(
                        'o:undo_job' => array('o:id' => $job->getId() )
                    )
                );
    }
}
