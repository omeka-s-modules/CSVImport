<?php

namespace CSVImport\Form;

use Laminas\Form\Form;
use Laminas\Form\Element\Checkbox;

class MappingModelSaveForm extends Form
{

    public function init()
    {
        $this->setAttribute('action', 'mapping-model/save');

        $this->add([
            'name'=> 'job_id',
            'type'=> 'hidden',
            'attributes' => [
                'value' => $this->getOption('job_id'),
            ]
        ]);

        $this->add([
            'name' => 'mapping_name',
            'type' => 'text',
            'options' => [
                'label' => 'Mapping model name', //@translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'override_mapping',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Override mapping model if it already exists?', //@translate
            ],
        ]);
    }
}
