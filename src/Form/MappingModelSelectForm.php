<?php
namespace CSVImport\Form;

use CSVImport\Form\Element\MappingModelSelect;
use Laminas\Form\Form;

class MappingModelSelectForm extends Form
{
    public function init()
    {
        $this->setAttribute('action', '/admin/csvimport/mapping-model/selectMappingModel');

        $this->add([
                    'name' => 'mapping_id',
                    'type' => MappingModelSelect::class,
                    'attributes' => [
                        'id' => 'mapping-id',
                        'class' => 'chosen-select',
                        'multiple' => false,
                        'data-placeholder' => '',
                    ],
                    'options' => [
                        'label' => 'Select mapping model', // @translate
                        'resource_value_options' => [
                            'resource' => 'csvimport_mapping_models',
                            'query' => [],
                        ],
                    ],
        ]);
    }
}
