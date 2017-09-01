<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use CSVImport\CsvFile;

class Import extends AbstractJob
{
    protected $api;

    protected $addedCount;

    protected $logger;

    protected $hasErr = false;

    public function perform()
    {
        ini_set("auto_detect_line_endings", true);
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $config = $this->getServiceLocator()->get('Config');
        $resourceType = $this->getArg('resource_type', 'items');
        $mappingClasses = $config['csv_import_mappings'][$resourceType];
        $mappings = [];
        $args = $this->job->getArgs();
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass($args, $this->getServiceLocator());
        }
        $csvFile = new CsvFile($this->getServiceLocator());
        $csvFile->setTempPath($this->getArg('csvpath'));
        $csvFile->loadFromTempPath();
        $csvImportJson = [
                            'o:job' => ['o:id' => $this->job->getId()],
                            'comment' => 'Job started',
                            'resource_type' => $resourceType,
                            'added_count' => 0,
                            'has_err' => false,
                          ];

        $response = $this->api->create('csvimport_imports', $csvImportJson);
        $importRecordId = $response->getContent()->id();
        $insertJson = [];
        foreach ($csvFile->fileObject as $index => $row) {
            //skip the first (header) row, and any blank ones
            if ($index == 0 || empty($row)) {
                continue;
            }

            $entityJson = [];
            foreach ($mappings as $mapping) {
                $entityJson = array_merge($entityJson, $mapping->processRow($row));
                if ($mapping->getHasErr()) {
                    $this->hasErr = true;
                }
            }
            $insertJson[] = $entityJson;
            //only add every X for batch import
            if ($index % 20 == 0) {
                //batch create
                $this->createEntities($insertJson);
                $insertJson = [];
            }
        }

        //take care of remainder from the modulo check
        $this->createEntities($insertJson);

        $comment = $this->getArg('comment');

        $csvImportJson = [
                            'comment' => $comment,
                            'added_count' => $this->addedCount,
                            'has_err' => $this->hasErr,
                          ];
        $response = $this->api->update('csvimport_imports', $importRecordId, $csvImportJson);
        $csvFile->delete();
    }

    protected function createEntities($toCreate)
    {
        $resourceType = $this->getArg('resource_type', 'items');
        $createResponse = $this->api->batchCreate($resourceType, $toCreate, [], ['continueOnError' => true]);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $createImportEntitiesJson = [];

        foreach ($createContent as $resourceReference) {
            $createImportEntitiesJson[] = $this->buildImportRecordJson($resourceReference);
        }
        $createImportRecordResponse = $this->api->batchCreate('csvimport_entities', $createImportEntitiesJson, [], ['continueOnError' => true]);
    }

    protected function buildImportRecordJson($resourceReference)
    {
        $recordJson = ['o:job' => ['o:id' => $this->job->getId()],
                                  'entity_id' => $resourceReference->id(),
                                  'resource_type' => $this->getArg('entity_type', 'items'),
                            ];
        return $recordJson;
    }
}
