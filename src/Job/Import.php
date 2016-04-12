<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;
use Omeka\Job\Exception as JobException;
use CSVImport\CsvFile;

class Import extends AbstractJob
{
    protected $api;

    protected $addedCount;

    protected $logger;

    public function perform()
    {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $config = $this->getServiceLocator()->get('Config');
        $mappingClasses = $config['csv_import_mappings'];
        $mappings = [];
        $args = $this->job->getArgs();
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass($args, $this->getServiceLocator());
        }

        $csvFile = new CsvFile($this->getServiceLocator());
        $csvFile->setTempPath($this->getArg('csvpath'));
        $csvFile->loadFromTempPath();
        $csvImportJson = array(
                            'o:job'         => array('o:id' => $this->job->getId()),
                            'comment'       => 'Job started',
                            'added_count'   => 0,
                            'updated_count' => 0
                          );

        $response = $this->api->create('csvimport_imports', $csvImportJson);
        $importRecordId = $response->getContent()->id();
        $insertJson = [];
        foreach($this->csvFile->fileObject as $index => $row) {
            //skip the first (header) row, and any blank ones
            if ($index == 0 || empty($row)) {
                continue;
            }

            $itemJson = [];
            foreach($mappings as $mapping) {
                $itemJson = array_merge($itemJson, $mapping->processRow($row));
            }
            $insertJson[] = $itemJson;
            //only add every X for batch import
            if ( $index % 20 == 0 ) {
                //batch create
                $this->createItems($insertJson);
                $insertJson = [];
            }
        }
        //take care of remainder from the modulo check
        $this->createItems($insertJson);
        
        $comment = $this->getArg('comment');
        $csvImportJson = array(
                            'comment'       => $comment,
                            'added_count'   => $this->addedCount,
                            'updated_count' => 0
                          );

        $response = $this->api->update('csvimport_imports', $importRecordId, $csvImportJson);
    }

    protected function createItems($toCreate) 
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), true);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $this->logger->debug($this->addedCount);
        $createImportRecordsJson = array();

        foreach($createContent as $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($resourceReference);
        }
        $this->logger->debug('before records');
        $createImportRecordResponse = $this->api->batchCreate('csvimport_records', $createImportRecordsJson, array(), true);
        $this->logger->debug('after records');
    }

    protected function buildImportRecordJson($resourceReference) 
    {
        $recordJson = array('o:job'     => ['o:id' => $this->job->getId()],
                            'o:item'    => ['o:id' => $resourceReference->id()],
                            );
        return $recordJson;
    }
}
