<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;
use Omeka\Job\Exception as JobException;

class Import extends AbstractJob
{
    protected $client;

    protected $endpoint;

    protected $api;

    protected $termIdMap;

    protected $addedCount;

    protected $updatedCount;

    protected $typeMap;

    protected $elementMap;
    
    protected $htmlElementMap;
    
    protected $dctermsTitleId;
    
    protected $logger;

    public function perform()
    {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->logger->addWriter(new JobWriter($this->job));

    }

}
