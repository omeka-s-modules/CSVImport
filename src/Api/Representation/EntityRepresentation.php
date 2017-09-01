<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class EntityRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'o:job' => $this->job()->getReference(),
            'entity_id' => $this->entityId(),
            'resource_type' => $this->resourceType(),
        ];
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

    public function entityId()
    {
        return $this->resource->getEntityId();
    }

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }
}
