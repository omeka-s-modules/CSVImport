<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class RecordRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'o:item'        => $this->item()->getReference(),
            'o:job'         => $this->job()->getReference(),
        );
    }

    public function getJsonLdType()
    {
        return 'o:CsvimportRecord';
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }
}
