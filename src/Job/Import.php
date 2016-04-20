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
        $entityType = $this->getArg('resource_type', 'items');
        $mappingClasses = $config['csv_import_mappings'][$entityType];
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
                            'resource_type'   => $entityType,
                            'added_count'   => 0,
                          );

        $response = $this->api->create('csvimport_imports', $csvImportJson);
        $importRecordId = $response->getContent()->id();
        $insertJson = [];
        foreach($csvFile->fileObject as $index => $row) {
            //skip the first (header) row, and any blank ones
            if ($index == 0 || empty($row)) {
                continue;
            }

            $entityJson = [];
            foreach($mappings as $mapping) {
                $entityJson = array_merge($entityJson, $mapping->processRow($row));
            }
            $insertJson[] = $entityJson;
            //only add every X for batch import
            if ( $index % 20 == 0 ) {
                //batch create
                $this->createEntities($insertJson);
                $insertJson = [];
            }
        }
        //take care of remainder from the modulo check
        $this->createEntities($insertJson);
        
        $comment = $this->getArg('comment');
        $csvImportJson = array(
                            'comment'       => $comment,
                            'added_count'   => $this->addedCount,
                          );

        $response = $this->api->update('csvimport_imports', $importRecordId, $csvImportJson);
    }

    protected function createEntities($toCreate) 
    {
        $entityType = $this->getArg('entity_type', 'items');
        $createResponse = $this->api->batchCreate($entityType, $toCreate, array(), true);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $createImportRecordsJson = array();

        foreach($createContent as $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($resourceReference);
        }
        $createImportRecordResponse = $this->api->batchCreate('csvimport_entities', $createImportRecordsJson, array(), true);
    }

    protected function buildImportRecordJson($resourceReference) 
    {
        $recordJson = array('o:job'         => ['o:id' => $this->job->getId()],
                            'entity_id'      => $resourceReference->id(),
                            'resource_type' => $this->getArg('entity_type', 'items'),
                            );
        return $recordJson;
    }
}
