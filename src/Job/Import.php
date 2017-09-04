<?php
namespace CSVImport\Job;

use CSVImport\CsvFile;
use CSVImport\Entity\CSVImportImport;
use Doctrine\DBAL\Connection;
use LimitIterator;
use Omeka\Api\Manager;
use Omeka\Job\AbstractJob;
use Zend\Log\Logger;

class Import extends AbstractJob
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_UPDATE_ELSE_CREATE = 'update else create';
    const ACTION_DELETE = 'delete';
    const ACTION_SKIP = 'skip';

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

        // Check options.
        $identifierProperty = empty($args['identifier_property']) ? null : $args['identifier_property'];
        $action = empty($args['action'])
            ? (empty($identifierProperty) ? self::ACTION_CREATE : self::ACTION_UPDATE_ELSE_CREATE)
            : $args['action'];
        $this->checkOptions(compact('action', 'identifierProperty'));
        if ($this->hasErr) {
            return $this->endJob();
        }

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

            switch ($action) {
                case self::ACTION_CREATE:
                    $this->create($data);
                    break;
                case self::ACTION_UPDATE:
                    $identifiers = $this->extractIdentifiers($data, $identifierProperty);
                    $ids = $this->findResourceIdsFromIdentifiers($identifiers, $identifierProperty);
                    $this->update($data, $ids, false);
                    break;
                case self::ACTION_UPDATE_ELSE_CREATE:
                    $identifiers = $this->extractIdentifiers($data, $identifierProperty);
                    $ids = $this->findResourceIdsFromIdentifiers($identifiers, $identifierProperty);
                    $this->update($data, $ids, true);
                    break;
                case self::ACTION_DELETE:
                    $identifiers = $this->extractIdentifiers($data, $identifierProperty);
                    $ids = $this->findResourceIdsFromIdentifiers($identifiers, $identifierProperty);
                    $this->delete($ids);
                    break;
                case self::ACTION_SKIP:
                    // No action to do.
                    break;
            }

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

    /**
     * Batch update a list of entities.
     *
     * @param array $data
     * @param array $ids
     * @param bool $createIfNotFound
     */
    protected function update($data, $ids, $createIfNotFound = false)
    {
        $this->hasErr = true;
        $this->logger->err(sprintf('The action "Update" is not managed currently.'));
    }

    /**
     * Batch delete a list of entities.
     *
     * @param array $ids
     */
    protected function delete($ids)
    {
        $ids = array_unique(array_filter($ids));
        $this->hasErr = true;
        $this->logger->err(sprintf('The action "Delete" is not managed currently.'));
    }

    /**
     * Helper to find identifiers from a batch of rows.
     *
     * @param array $data
     * @param int $identifierProperty
     * @return array Associative array mapping the data key as key and the found
     * ids or null as value. Order is kept.
     */
    protected function extractIdentifiers($data, $identifierProperty)
    {
        $identifiers = [];
        $resourceType = $this->getArg('resource_type', 'items');
        foreach ($data as $key => $entityJson) {
            $identifier = null;
            switch ($resourceType) {
                case 'items':
                    foreach ($entityJson as $index => $value) {
                        if (is_array($value) && !empty($value)) {
                            $value = reset($value);
                            if (isset($value['property_id'])
                                && $value['property_id'] === $identifierProperty
                                && isset($value['@value'])
                                && strlen($value['@value'])
                            ) {
                                $identifier = $value['@value'];
                                break;
                            }
                        }
                    }
                    break;
                case 'users':
                    break;
            }
            $identifiers[$key] = $identifier;
        }
        return $identifiers;
    }

    /**
     * Helper to find a list of resource ids from a list of identifiers.
     *
     * @param array $identifiers Identifiers should be unique.
     * @param string|int $identifierProperty
     * @return array Associative array with the identifiers as key and the ids
     * or null as value. Order is kept, but duplicate identifiers are removed.
     */
    protected function findResourceIdsFromIdentifiers($identifiers, $identifierProperty)
    {
        $identifiers = array_unique(array_filter($identifiers));
        if (empty($identifiers)) {
            return [];
        }

        $resourceType = $this->getArg('resource_type', 'items');
        $resourceTypes = [
            'item_sets' => 'Omeka\Entity\ItemSet',
            'items' => 'Omeka\Entity\Item',
            'media' => 'Omeka\Entity\Media',
            // Avoid a check.
            'Omeka\Entity\ItemSet' => 'Omeka\Entity\ItemSet',
            'Omeka\Entity\Item' => 'Omeka\Entity\Item',
            'Omeka\Entity\Media' => 'Omeka\Entity\Media',
        ];
        if (!isset($resourceTypes[$resourceType])) {
            return [];
        }

        // The api manager doesn't manage this type of search.
        $conn = $this->getServiceLocator()->get('Omeka\Connection');

        switch ($identifierProperty) {
            case 'internal_id':
                $identifiers = array_map('intval', $identifiers);
                $quotedIdentifiers = implode(',', $identifiers);
                $qb = $conn->createQueryBuilder()
                    ->select('resource.id')
                    ->from('resource', 'resource')
                    ->andWhere('resource.resource_type = :resource_type')
                    ->setParameter(':resource_type', $resourceTypes[$resourceType])
                    // ->andWhere('resource.id in (:ids)')
                    // ->setParameter(':ids', $identifiers)
                    ->andWhere("resource.id in ($quotedIdentifiers)")
                    ->addOrderBy('resource.id', 'ASC');
                $stmt = $conn->executeQuery($qb, $qb->getParameters());
                $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $result = array_combine($result, $result);
                break;

            default:
                // Search in multiple resource types in one time.
                $quotedIdentifiers = array_map([$conn, 'quote'], $identifiers);
                $quotedIdentifiers = implode(',', $quotedIdentifiers);
                $qb = $conn->createQueryBuilder()
                    ->select('value.value', 'value.resource_id')
                    ->from('value', 'value')
                    ->leftJoin('value', 'resource', 'resource', 'value.resource_id = resource.id')
                    ->andWhere('resource.resource_type = :resource_type')
                    ->setParameter(':resource_type', $resourceTypes[$resourceType])
                    ->andwhere('value.property_id = :property_id')
                    ->setParameter(':property_id', $identifierProperty)
                    // ->andWhere('value.value in (:values)')
                    // ->setParameter(':values', $identifiers)
                    ->andWhere("value.value in ($quotedIdentifiers)")
                    ->addOrderBy('resource.id', 'ASC')
                    ->addOrderBy('value.id', 'ASC');
                $stmt = $conn->executeQuery($qb, $qb->getParameters());
                $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
                break;
        }

        // Reorder the result according to the input (simpler in php).
        return array_replace(array_fill_keys($identifiers, null), $result);
    }

    /**
     * Check options used to import.
     *
     * @param array $options Associative array of options.
     */
    protected function checkOptions(array $options)
    {
        extract($options);
        $resourceType = $this->getArg('resource_type', 'items');

        $allowedActions = [
            self::ACTION_CREATE,
            self::ACTION_APPEND,
            self::ACTION_UPDATE,
            self::ACTION_REPLACE,
            self::ACTION_DELETE,
            self::ACTION_SKIP,
        ];
        if (!in_array($action, $allowedActions)) {
            $this->hasErr = true;
            $this->logger->err(sprintf('Unknown action "%s".', $action));
        }
        // Another specific check.
        elseif (!in_array($action, [self::ACTION_CREATE, self::ACTION_SKIP])) {
            if (empty($identifierProperty)) {
                $this->hasErr = true;
                $this->logger->err(sprintf('The action "%s" requires a resource identifier property.', $action)); // @translate
            }
            if ($action !== self::ACTION_DELETE && !in_array($resourceType, ['items'])) {
                $this->hasErr = true;
                $this->logger->err(sprintf('The action "%s" is not available for resource type "%s" currently.', // @translate
                    $action, $resourceType));
            }
        }
        if ($identifierProperty === 'internal_id') {
            $this->hasErr = true;
            $this->logger->err(sprintf('The identifier property "internal_id" is not managed currently.')); // @translate
        }
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
