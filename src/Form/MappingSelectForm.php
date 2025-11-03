<?php
namespace CSVImport\Form;

use CSVImport\Form\Element\MappingSelect;
use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;
use Laminas\Form\Element\Hidden;

class MappingSelectForm extends Form
{

    public function init()
    {
        $this->setAttribute('action', '/admin/csvimport/mapping/selectMapping');

        $this->add([
                    'name' => 'mapping_id',
                    'type' => MappingSelect::class,
                    'attributes' => [
                        'id' => 'mapping-id',
                        'class' => 'chosen-select',
                        'multiple' => false,
                        'data-placeholder' => '',
                    ],
                    'options' => [
                        'label' => 'Select mapping', // @translate
                        'resource_value_options' => [
                            'resource' => 'csvimport_mappings',
                            'query' => [],
                        ],
                    ],
        ]);
    }
}