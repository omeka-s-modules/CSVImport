<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'mapping';
    }

    public function getJsonLd()
    {
        return [
            'name' => $this->name(),
            'mapping' => $this->mapping(),
            'created' => $this->created(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:CSVimportMapping';
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function mapping()
    {
        return $this->resource->getMapping();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/csvimport/mapping-id',
            [
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }
}
