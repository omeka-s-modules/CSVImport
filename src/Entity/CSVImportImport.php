<?php
namespace CSVImport\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;

/**
 * @Entity
 */
class CSVImportImport extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=true)
     */
    protected $undoJob;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $comment;

    /**
     * @Column(type="string")
     */
    protected $resource_type;

    /**
     * @Column(type="boolean")
     */
    protected $has_err;

    /**
     * @Column(type="json_array")
     */
    protected $stats;

    public function getId()
    {
        return $this->id;
    }

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setUndoJob(Job $job)
    {
        $this->undoJob = $job;
    }

    public function getUndoJob()
    {
        return $this->undoJob;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setResourceType($resourceType)
    {
        $this->resource_type = $resourceType;
    }

    public function getResourceType()
    {
        return $this->resource_type;
    }

    public function setHasErr($hasErr)
    {
        $this->has_err = $hasErr;
    }

    public function getHasErr()
    {
        return $this->has_err;
    }

    public function setStats($stats)
    {
        $this->stats = $stats;
    }

    public function getStats()
    {
        return $this->stats;
    }
}
