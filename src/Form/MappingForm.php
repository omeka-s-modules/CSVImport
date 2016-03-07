<?php
namespace CSVImport\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

    }
}