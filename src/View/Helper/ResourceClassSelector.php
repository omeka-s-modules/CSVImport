<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ResourceClassSelector extends AbstractHelper
{
    /**
     * Return the resource class selector form control.
     *
     * @return string
     */
    public function __invoke($text = null, $active = true)
    {
        $response = $this->getView()->api()->search('vocabularies');
        if ($response->isError()) {
            return;
        }

        $valueOptions = array();
        foreach ($response->getContent() as $vocabulary) {
            $options = array();
            foreach ($vocabulary->resourceClasses() as $resourceClass) {
                $options[$resourceClass->id()] = $resourceClass->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = array(
                'label' => $vocabulary->label(),
                'options' => $options,
            );
        }

        return $this->getView()->partial(
            'csv-import/common/resource-class-selector',
            array(
                'vocabularies'       => $response->getContent(),
                'text'               => $text,
                'state'              => $active ? 'active' : ''
            )
        );
    }
}
