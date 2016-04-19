<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class EntityRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'o:job'         => $this->job()->getReference(),
        );
    }

    public function getJsonLdType()
    {
        return 'o:CsvimportEntity';
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }
}
