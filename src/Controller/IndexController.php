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
                $post = $this->params()->fromPost();
                $map = $post['column-property'];
                $csvPath = $post['csvpath'];
                $csvFile = new CsvFile($this->getServiceLocator());
                $csvFile->setTempPath($csvPath);
                $csvFile->loadFromTempPath();
                $data = $csvFile->getDataRows();
                print_r($map);
                print_r($data);
                die();
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
            
            $view->setVariable('form', $form);
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