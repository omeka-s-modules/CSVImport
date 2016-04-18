<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class EntityRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'o:entity'        => $this->entity()->getReference(),
            'o:job'         => $this->job()->getReference(),
        );
    }

    public function getJsonLdType()
    {
        return 'o:CsvimportEntity';
    }

    public function entity()
    {
        //@todo abstract out for entity, not item
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }
}
