<?php
namespace CSVImport\Job;

use CSVImport\CsvFile;
use CSVImport\Entity\CSVImportImport;
use LimitIterator;
use Omeka\Api\Manager;
use Omeka\Job\AbstractJob;
use Zend\Log\Logger;

class Import extends AbstractJob
{
    /**
     * Number of rows to process by batch.
     *
     * @var int
     */
    protected $rowsByBatch = 20;

    /**
     * @var Manager
     */
    protected $api;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CSVImportImport
     */
    protected $importRecord;

    /**
     * @var CsvFile
     */
    protected $csvFile;

    /**
     * @var int
     */
    protected $addedCount;

    /**
     * @var bool
     */
    protected $hasErr = false;

    public function perform()
    {
        ini_set("auto_detect_line_endings", true);
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $config = $this->getServiceLocator()->get('Config');
        $resourceType = $this->getArg('resource_type', 'items');
        $mappingClasses = $config['csv_import_mappings'][$resourceType];

        $args = $this->job->getArgs();

        $mappings = [];
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass($args, $this->getServiceLocator());
        }

        $this->csvFile = new CsvFile($this->getServiceLocator()->get('Config'));
        $csvFile = $this->csvFile;
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
        $this->importRecord = $response->getContent();

        // Skip the first (header) row, and blank ones (cf. CsvFile object).
        $offset = 1;
        $file = $csvFile->fileObject;
        $file->rewind();
        while ($file->valid()) {
            $data = [];
            foreach (new LimitIterator($file, $offset, $this->rowsByBatch) as $row) {
                $row = array_map('trim', $row);
                $entityJson = [];
                foreach ($mappings as $mapping) {
                    $entityJson = array_merge($entityJson, $mapping->processRow($row));
                    if ($mapping->getHasErr()) {
                        $this->hasErr = true;
                    }
                }
                $data[] = $entityJson;
            }

            $this->create($data);

            // The next offset is not the previous offset + the batch size but
            // the current key (read ahead), because there may be empty lines.
            // The file may be empty in case of incomplete batch at the end.
            $offset = $file ? $file->key() : null;
        }

        $this->endJob();
    }

    /**
     * Batch create a list of entities.
     *
     * @param array $data
     */
    protected function create(array $data)
    {
        if (empty($data)) {
            return;
        }
        $resourceType = $this->getArg('resource_type', 'items');
        $createResponse = $this->api->batchCreate($resourceType, $data, [], ['continueOnError' => true]);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);

        $createImportEntitiesJson = [];
        foreach ($createContent as $resourceReference) {
            $createImportEntitiesJson[] = $this->buildImportRecordJson($resourceReference);
        }
        $createImportRecordResponse = $this->api->batchCreate(
            'csvimport_entities', $createImportEntitiesJson, [], ['continueOnError' => true]);
    }

    protected function buildImportRecordJson($resourceReference)
    {
        $recordJson = [
            'o:job' => ['o:id' => $this->job->getId()],
            'entity_id' => $resourceReference->id(),
            'resource_type' => $this->getArg('entity_type', 'items'),
        ];
        return $recordJson;
    }

    protected function endJob()
    {
        $csvImportJson = [
            'comment' => $this->getArg('comment'),
            'added_count' => $this->addedCount,
            'has_err' => $this->hasErr,
        ];
        $response = $this->api->update('csvimport_imports', $this->importRecord->id(), $csvImportJson);
        $this->csvFile->delete();
    }
}
