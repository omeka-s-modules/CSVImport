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
        $form = new ImportForm($this->getServiceLocator());
        $view->form = $form;
        return $view;
    }
    
    public function mapAction()
    {
        $view = new ViewModel;
        $form = new MappingForm($this->getServiceLocator());
        $view->setVariable('form', $form);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles()->toArray();
            if (empty($files)) {
                $post = $this->params()->fromPost();
                $map = $post['column-property'];
                $csvPath = $post['csvpath'];

                $args = [
                    'csvPath'   => $csvPath,
                    'columnMap' => $map,
                    'fileMap'   => [],
                ];
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $job = $dispatcher->dispatch('CSVImport\Job\Import', $args);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                //die();
            } else {
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );
                
                $tmpFile = $post['csv']['tmp_name'];
                $csvFile = new CsvFile($this->getServiceLocator());
                $csvPath = $csvFile->getTempPath();
                $csvFile->moveToTemp($tmpFile);
                $csvFile->loadFromTempPath();
                $columns = $csvFile->getHeaders();
                
                
                $view->setVariable('columns', $columns);
                $view->setVariable('csvpath', $csvPath);
                //print_r($post);
                //print_r($columns);
                //die();
            }
        }
        return $view;
    }
    
    public function pastImportsAction()
    {
        $view = new ViewModel;
        return $view;
    }
}