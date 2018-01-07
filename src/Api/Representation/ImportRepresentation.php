<?php
namespace CSVImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ImportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $undoJob = $this->undoJob();
        if ($undoJob) {
            $undoJob = $undoJob->getReference();
        }

        return [
            'comment' => $this->comment(),
            'resource_type' => $this->resourceType(),
            'has_err' => $this->hasErr(),
            'stats' => $this->stats(),
            'o:job' => $this->job()->getReference(),
            'o:undo_job' => $undoJob,
        ];
    }

    public function getJsonLdType()
    {
        return 'o:CSVimportImport';
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }

    public function undoJob()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getUndoJob());
    }

    public function comment()
    {
        return $this->resource->getComment();
    }

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }

    public function hasErr()
    {
        return $this->resource->getHasErr();
    }

    public function stats()
    {
        return $this->resource->getStats();
    }
}
