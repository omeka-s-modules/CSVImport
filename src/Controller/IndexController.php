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
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles()->toArray();
            if (empty($files)) {
                
            } else {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            
            $tmpFile = $post['csv']['tmp_name'];
            $csvFile = new CsvFile($this->getServiceLocator());
            $csvFile->getTempPath();
            $csvFile->moveToTemp($tmpFile);
            $csvFile->loadFromTempPath();
            $columns = $csvFile->getHeaders();
            $data = $csvFile->getDataRows();
            $view->setVariable('form', $form);
            $view->setVariable('columns', $columns);
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