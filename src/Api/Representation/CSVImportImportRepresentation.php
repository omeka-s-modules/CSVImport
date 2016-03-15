<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OmekaimportImportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $undo_job = null;
        if($this->undoJob()) {
            $undo_job = $this->undoJob()->getReference();
        }

        return array(
            'added_count'    => $this->addedCount(),
            'updated_count'  => $this->updatedCount(),
            'comment'        => $this->comment(),
            'o:job'          => $this->job()->getReference(),
            'o:undo_job'     => $undo_job
        );
    }
    
    public function getJsonLdType()
    {
        return 'o:OmekaimportImport';
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

    public function addedCount()
    {
        return $this->resource->getAddedCount();
    }

    public function updatedCount()
    {
        return $this->resource->getUpdatedCount();
    }
}
