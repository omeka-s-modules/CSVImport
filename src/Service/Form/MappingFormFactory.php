<?php
namespace CSVImport\Service\Form;

use Zend\ServiceManager\setCreationOptions;

use CSVImport\Form\MappingForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MappingFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new MappingForm(null, $this->options);
        $form->setServiceLocator($elements->getServiceLocator());
        return $form;
    }

    public function setCreationOptions($creationOptions)
    {
        $this->options = $creationOptions;
    }
}
