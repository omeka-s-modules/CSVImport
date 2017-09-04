<?php
namespace CSVImport\Job;

use CSVImport\CsvFile;
use CSVImport\Entity\CSVImportImport;
use Doctrine\DBAL\Connection;
use LimitIterator;
use Omeka\Api\Manager;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Omeka\Job\AbstractJob;
use Zend\Log\Logger;

class Import extends AbstractJob
{
    const ACTION_CREATE = 'create'; // @translate
    const ACTION_APPEND = 'append'; // @translate
    const ACTION_UPDATE = 'update'; // @translate
    const ACTION_REPLACE = 'replace'; // @translate
    const ACTION_DELETE = 'delete'; // @translate
    const ACTION_SKIP = 'skip'; // @translate

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
        $action = empty($args['action']) ? self::ACTION_CREATE : $args['action'];
        $identifierProperty = empty($args['identifier_property']) ? null : $args['identifier_property'];
        $actionUnidentified = empty($args['action_unidentified']) ? self::ACTION_SKIP : $args['action_unidentified'];
        $this->checkOptions(compact('action', 'identifierProperty', 'actionUnidentified'));
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
                case self::ACTION_APPEND:
                case self::ACTION_UPDATE:
                case self::ACTION_REPLACE:
                    $identifiers = $this->extractIdentifiers($data, $identifierProperty);
                    $ids = $this->findResourceIdsFromIdentifiers($identifiers, $identifierProperty);
                    $ids = $this->assocIdentifierKeysAndIds($identifiers, $ids);
                    $idsToProcess = array_filter($ids);
                    $idsRemaining = array_diff_key($ids, $idsToProcess);
                    $dataToProcess = array_intersect_key($data, $idsToProcess);
                    // The creation occurs before the update in all cases.
                    switch ($actionUnidentified) {
                        case self::ACTION_CREATE:
                            $dataToCreate = array_intersect_key($data, $idsRemaining);
                            $this->create($dataToCreate);
                            break;
                        case self::ACTION_SKIP:
                            if ($idsRemaining) {
                                $identifiersRemaining = array_intersect_key($identifiers, $idsRemaining);
                                $this->logger->info(sprintf('The following identifiers are not associated with a resource and were skipped: "%s".', // @translate
                                    implode('", "', $identifiersRemaining)));
                            }
                            break;
                    }
                    $this->update($dataToProcess, $idsToProcess, $action);
                    break;
                case self::ACTION_DELETE:
                    $identifiers = $this->extractIdentifiers($data, $identifierProperty);
                    $ids = $this->findResourceIdsFromIdentifiers($identifiers, $identifierProperty);
                    $idsToProcess = array_filter($ids);
                    $idsRemaining = array_diff_key($ids, $idsToProcess);
                    if ($idsRemaining) {
                        $identifiersRemaining = array_intersect_key($identifiers, $idsRemaining);
                        $this->logger->info(sprintf('The following identifiers are not associated with a resource and were skipped: "%s".', // @translate
                            implode('", "', $identifiersRemaining)));
                    }
                    $this->delete($idsToProcess);
                    break;
                case self::ACTION_SKIP:
                    // No process.
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
     * @param array $ids All the ids must exists and the order must be the same
     * than data.
     * @param string $mode
     */
    protected function update(array $data, array $ids, $mode)
    {
        if (empty($ids)) {
            return;
        }

        $resourceType = $this->getArg('resource_type', 'items');
        $fileData = [];
        $options = [];
        switch ($resourceType) {
            case 'items':
                // TODO Manage and update file data.
                switch ($mode) {
                    case self::ACTION_APPEND:
                        $options['isPartial'] = true;
                        $options['collectionAction'] = 'append';
                        break;
                    case self::ACTION_UPDATE:
                        $options['isPartial'] = true;
                        $options['collectionAction'] = array_key_exists('o:item_set', reset($data))
                            ? 'replace'
                            : 'append';
                        break;
                    case self::ACTION_REPLACE:
                        $options['isPartial'] = false;
                        $options['collectionAction'] = 'replace';
                        break;
                }
                break;
            default:
                $this->hasErr = true;
                $this->logger->err(sprintf('The update mode "%s" is unsupported for %s currently.', // @translate
                    $mode, $resourceType));
                return;
        }

        // In the api manager, batchUpdate() allows to update a set of resources
        // with the same data. Here, data are specific to each row.
        $updatedIds = [];
        foreach ($ids as $key => $id) {
            try {
                $response = $this->api->update($resourceType, $id, $data[$key], $fileData, $options);
                if ($mode === self::ACTION_APPEND) {
                    // TODO Improve to avoid two consecutive update (deduplicate before or via core methods).
                    $resource = $response->getContent();
                    $this->deduplicatePropertyValues($resource);
                }
                $updatedIds[] = $id;
            } catch (\Exception $e) {
                $this->logger->err((string) $e);
                continue;
            }
        }
        $this->logger->info(sprintf('%d %s were updated (%s): %s.', // @translate
            count($updatedIds), $resourceType, $mode, implode(', ', $updatedIds)));
    }

    /**
     * Batch delete a list of entities.
     *
     * @param array $ids
     */
    protected function delete(array $ids)
    {
        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            return;
        }
        $resourceType = $this->getArg('resource_type', 'items');
        $response = $this->api->batchDelete($resourceType, $ids, [], ['continueOnError' => true]);
        $deleted = $response->getContent();
        // TODO Get better stats of removed ids in case of error.
        $this->logger->info(sprintf('%d %s were removed: %s.', // @translate
            count($deleted), $resourceType, implode(', ', $ids)));
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
     * Helper to map data keys and ids in order to keep duplicate identifiers.
     *
     * When a document use multiple lines of data, consecutive or not, they have
     * the same identifiers, but they are lost during the database search, that
     * returns an simple associative array.
     *
     * @param array $identifiers Associative array of data ids and identifiers.
     * @param array $ids Associative array of unique identifiers and ids.
     * @return array
     */
    protected function assocIdentifierKeysAndIds(array $identifiers, array $ids)
    {
        return array_map(function ($v) use ($ids) {
            return $v ? $ids[$v] : null;
        }, $identifiers);
    }

    /**
     * Keep only data with an existing identifier.
     *
     * @param array $data
     * @param array $identifiers Associative array of identifiers and data ids.
     * @return array
     */
    protected function filterDataWithIdentifier(array $data, array $identifiers)
    {
        $identifiers = array_filter($identifiers, function ($v) { return !empty($v); });
        return array_intersect_key($data, $identifiers);
    }

    /**
     * Keep only data without an existing identifier.
     *
     * @param array $data
     * @param array $identifiers Associative array of data ids and identifiers.
     * @return array
     */
    protected function filterDataWithoutIdentifier(array $data, array $identifiers)
    {
        $identifiers = array_filter($identifiers);
        return array_intersect_key($data, $identifiers);
    }

    /**
     * Deduplicate property values of a resource.
     *
     * @param AbstractResourceRepresentation $representation
     */
    protected function deduplicatePropertyValues(AbstractResourceRepresentation $representation)
    {
        // NOTE The partial update does not seem to work, so take flushed data
        // and replace them all.
        // $data = [];
        $data = $representation->jsonSerialize();
        $values = $representation->values();
        foreach ($values as $term => $propertyData) {
            $data[$term] = array_values(array_map('unserialize', array_unique( array_map(function ($v) {
                return serialize($v->jsonSerialize());
            }, $propertyData['values']))));
        }
        $resourceType = $this->getArg('resource_type', 'items');
        $options = [];
        // $options['isPartial'] = true;
        // $options['collectionAction'] = 'append';
        $options['isPartial'] = false;
        $options['collectionAction'] = 'replace';
        $response = $this->api->update($resourceType, $representation->id(), $data, [], $options);
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
        if (!in_array($action, [self::ACTION_CREATE, self::ACTION_DELETE, self::ACTION_SKIP])) {
            if (!in_array($actionUnidentified, [self::ACTION_SKIP, self::ACTION_CREATE])) {
                $this->hasErr = true;
                $this->logger->err(sprintf('The action "%s" for unidentified resources is not managed.', // @translate
                    $actionUnidentified));
            }
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
