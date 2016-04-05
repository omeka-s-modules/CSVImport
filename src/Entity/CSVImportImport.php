<?php
namespace CSVImport\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;

/**
 * @Entity
 */
class CSVImportImport extends AbstractEntity {

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
     * @Column(type="integer")
     */
    protected $addedCount;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=true)
     */
    protected $undoJob;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $comment;

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

    public function setAddedCount($count)
    {
        $this->addedCount = $count;
    }

    public function getAddedCount()
    {
        return $this->addedCount;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }
}
