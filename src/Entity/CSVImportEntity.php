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
class CSVImportEntity extends AbstractEntity
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
     * @var int
     */
    protected $entity;
    
    protected $entity_type;

    public function getId()
    {
        return $this->id;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    public function getEntityType()
    {
        return $this->entity_type;
    }

    public function setEntityType($entityType)
    {
        $this->entity_type = $entityType;
    }
    
    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }
}
