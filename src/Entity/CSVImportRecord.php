<?php
namespace CSVImport\Entity;

use DateTime;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;
use Omeka\Entity\Item;
use Omeka\Entity\ItemSet;

/**
 * @Entity
 */
class OmekaimportRecord extends AbstractEntity
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=true, onDelete="CASCADE")
     * @var int
     */
    protected $item;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\ItemSet")
     * @JoinColumn(nullable=true)
     * @var int
     */
    protected $itemSet;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $remoteType;
    
    /**
     * @Column(type="integer")
     * @var int
     */
    public $remoteId;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $endpoint;

    public function getId()
    {
        return $this->id;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }

    public function setItemSet(ItemSet $itemSet)
    {
        $this->itemSet = $itemSet;
    }
    
    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setEndpoint($uri)
    {
        $this->endpoint = $uri;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
    
    public function setRemoteType($type)
    {
        $this->remoteType = $type;
    }
    
    public function getRemoteType()
    {
        return $this->remoteType;
    }
    
    public function setRemoteId($id)
    {
        $this->remoteId = $id;
    }
    
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    public function setLastModified(DateTime $lastModified) 
    {
        $this->lastModified = $lastModified;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }
}