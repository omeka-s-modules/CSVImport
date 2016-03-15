<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OmekaimportRecordRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'last_modified' => $this->lastModified(),
            'endpoint'      => $this->endpoint(),
            'remote_type'   => $this->remoteType(),
            'remote_id'     => $this->remoteId(),
            'o:item'        => $this->item()->getReference(),
            'o:item_set'    => $this->itemSet()->getReference(),
            'o:job'         => $this->job()->getReference(),
        );
    }
    
    public function getJsonLdType()
    {
        return 'o:OmekaimportRecord';
    }

    public function lastModified()
    {
        return $this->resource->getlastModified();
    }

    public function endpoint()
    {
        return $this->resource->getEndpoint();
    }
    
    public function remoteType()
    {
        return $this->resource->getRemoteType();
    }
    
    public function remoteId()
    {
        return $this->resource->getRemoteId();
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->resource->getItemSet());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }

}
