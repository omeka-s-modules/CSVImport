<?php
namespace CSVImport\Job;

use CSVImport\CsvFile;
use CSVImport\Entity\CSVImportImport;
use CSVImport\Mvc\Controller\Plugin\FindResourcesFromIdentifiers;
use LimitIterator;
use Omeka\Api\Manager;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Omeka\Job\AbstractJob;
use Zend\Log\Logger;

class Import extends AbstractJob
{
    const ACTION_CREATE = 'create'; // @translate
    const ACTION_APPEND = 'append'; // @translate
    const ACTION_REVISE = 'revise'; // @translate
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
     * @var FindResourcesFromIdentifiers
     */
    protected $findResourcesFromIdentifiers;

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

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var array
     */
    protected $identifiers;

    /**
     * @var string|int
     */
    protected $identifierProperty;

    public function perform()
    {
        ini_set("auto_detect_line_endings", true);
        $services = $this->getServiceLocator();
        $this->api = $services->get('Omeka\ApiManager');
        $this->logger = $services->get('Omeka\Logger');
        $this->findResourcesFromIdentifiers = $services->get('ControllerPluginManager')
            ->get('findResourcesFromIdentifiers');
        $findResourcesFromIdentifiers = $this->findResourcesFromIdentifiers;
        $config = $services->get('Config');

        $this->resourceType = $this->getArg('resource_type', 'items');
        $args = $this->job->getArgs();

        $mappings = [];
        $mappingClasses = $config['csv_import']['mappings'][$this->resourceType];
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass($args, $services);
        }

        $this->resourceType = $this->getArg('resource_type', 'items');
        $this->csvFile = new CsvFile($config);
        $csvFile = $this->csvFile;
        if (isset($args['delimiter'])) {
            $csvFile->setDelimiter($args['delimiter']);
        }
        if (isset($args['enclosure'])) {
            $csvFile->setEnclosure($args['enclosure']);
        }
        if (isset($args['escape'])) {
            $csvFile->setEscape($args['escape']);
        }
        $csvFile->setTempPath($this->getArg('csvpath'));
        $csvFile->loadFromTempPath();

        $csvImportJson = [
            'o:job' => ['o:id' => $this->job->getId()],
            'comment' => 'Job started',
            'resource_type' => $this->resourceType,
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

        // The main identifier property may be used as term or as id in some
        // places, so prepare it one time only.
        if ($identifierProperty === 'internal_id') {
            $identifierPropertyId = $identifierProperty;
        } elseif (is_numeric($identifierProperty)) {
            $identifierPropertyId = (int) $identifierProperty;
        } else {
            $result = $this->api
                ->search('properties', ['term' => $identifierProperty])->getContent();
            $identifierPropertyId = $result ? $result[0]->id() : null;
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
                    $mapped = $mapping->processRow($row);
                    $entityJson = array_merge($entityJson, $mapped);
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
                case self::ACTION_REVISE:
                case self::ACTION_UPDATE:
                case self::ACTION_REPLACE:
                    $identifiers = $this->extractIdentifiers($data, $identifierPropertyId);
                    $ids = $findResourcesFromIdentifiers($identifiers, $identifierPropertyId, $this->resourceType);
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
                    $identifiers = $this->extractIdentifiers($data, $identifierPropertyId);
                    $ids = $findResourcesFromIdentifiers($identifiers, $identifierPropertyId, $this->resourceType);
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
        $createResponse = $this->api->batchCreate($this->resourceType, $data, [], ['continueOnError' => true]);
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

        $fileData = [];
        $options = [];
        switch ($this->resourceType) {
            case 'item_sets':
            case 'items':
            case 'media':
                // TODO Manage and update file data.
                switch ($mode) {
                    case self::ACTION_APPEND:
                        $options['isPartial'] = true;
                        $options['collectionAction'] = 'append';
                        break;
                    case self::ACTION_REVISE:
                        // Remove empty properties to avoid replacement.
                        // Warning: default may be automatically set already, so
                        // they are kept.
                        $data = $this->removeEmptyData($data);
                        // No break.
                    case self::ACTION_UPDATE:
                        $options['isPartial'] = true;
                        // TODO Check when some rows are empty and filled.
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
                    $mode, $this->resourceType));
                return;
        }

        // In the api manager, batchUpdate() allows to update a set of resources
        // with the same data. Here, data are specific to each row.
        $updatedIds = [];
        foreach ($ids as $key => $id) {
            try {
                $response = $this->api->update($this->resourceType, $id, $data[$key], $fileData, $options);
                if ($mode === self::ACTION_APPEND) {
                    // TODO Improve to avoid two consecutive update (deduplicate before or via core methods).
                    $resource = $response->getContent();
                    $this->deduplicatePropertyValues($resource);
                }
                $updatedIds[$key] = $id;
            } catch (\Exception $e) {
                $this->logger->err((string) $e);
                continue;
            }
        }
        $idsForLog = $this->idsForLog($updatedIds);
        $this->logger->info(sprintf('%d %s were updated (%s): %s.', // @translate
            count($updatedIds), $this->resourceType, $mode, $idsForLog));
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
        $response = $this->api->batchDelete($this->resourceType, $ids, [], ['continueOnError' => true]);
        $deleted = $response->getContent();
        // TODO Get better stats of removed ids in case of error.
        $idsForLog = $this->idsForLog($ids, true);
        $this->logger->info(sprintf('%d %s were removed: %s.', // @translate
            count($deleted), $this->resourceType, $idsForLog));
    }

    /**
     * Helper to find identifiers from a batch of rows.
     *
     * The list of associated identifiers are kept as a class property until it
     * is recalled with new identifiers.
     *
     * @param array $data
     * @param string|int $identifierPropertyId
     * @return array Associative array mapping the data key as key and the found
     * ids or null as value. Order is kept.
     */
    protected function extractIdentifiers($data, $identifierPropertyId = 'internal_id')
    {
        $identifiers = [];
        $identifierPropertyId = $identifierPropertyId ?: 'internal_id';

        foreach ($data as $key => $entityJson) {
            $identifier = null;
            switch ($identifierPropertyId) {
                case 'internal_id':
                    if (!empty($entityJson['o:id'])) {
                        $identifier = $entityJson['o:id'];
                    }
                    break;

                default:
                    switch ($this->resourceType) {
                        case 'items':
                            foreach ($entityJson as $index => $value) {
                                if (is_array($value) && !empty($value)) {
                                    $value = reset($value);
                                    if (isset($value['property_id'])
                                        && $value['property_id'] == $identifierPropertyId
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
            }
            $identifiers[$key] = $identifier;
        }

        $this->identifiers = $identifiers;
        return $identifiers;
    }

    /**
     * Helper to map data keys and ids in order to keep duplicate identifiers.
     *
     * When a document uses multiple lines of data, consecutive or not, they
     * have the same identifiers, but they are lost during the database search,
     * that returns a simple associative array.
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
        $identifiers = array_filter($identifiers, function ($v) {
            return !empty($v);
        });
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
     * Helper to get cleaner log when identifiers are used.
     *
     * @param array $ids
     * @param bool $hasIdentifierKeys
     * @return string
     */
    protected function idsForLog($ids, $hasIdentifierKeys = false)
    {
        switch ($this->identifierProperty) {
            case 'internal_id':
                // Nothing to do.
                break;
            default:
                if ($hasIdentifierKeys) {
                    array_walk($ids, function (&$v, $k) {
                        $v = sprintf('"%s" (%d)', $k, $v); // @ translate
                    });
                } else {
                    array_walk($ids, function (&$v, $k) {
                        $v = sprintf('"%s" (%d)', $this->identifiers[$k], $v); // @ translate
                    });
                }
                break;
        }
        return implode(', ', $ids);
    }

    /**
     * Remove empty values from passed data in order not to change current ones.
     *
     * @param array $data
     * @return array
     */
    protected function removeEmptyData(array $data)
    {
        // Data are updated in place.
        foreach ($data as &$dataValues) {
            foreach ($dataValues as $name => &$metadata) {
                switch ($name) {
                    case 'o:resource_template':
                    case 'o:resource_class':
                    case 'o:owner':
                    case 'o:item':
                    case 'o:media':
                        if (empty($metadata) || empty($metadata['o:id'])) {
                            unset($datavalues[$name]);
                        }
                        break;
                    case 'o:item-set':
                        if (empty($metadata)) {
                            unset($datavalues[$name]);
                        } elseif (array_key_exists('o:id', $metadata) && empty($metadata['o:id'])) {
                            unset($datavalues[$name]);
                        }
                        break;
                    case 'o:ingester':
                    case 'o:source':
                    case 'ingest_filename':
                        // These values are not updatable and are removed.
                        unset($datavalues[$name]);
                        break;
                    case 'o:is_public':
                    case 'o:is_open':
                        if (!in_array($metadata, [0, 1], true)) {
                            unset($datavalues[$name]);
                        }
                        break;
                    default:
                        if (is_array($metadata)) {
                            if (empty($metadata)) {
                                unset($datavalues[$name]);
                            }
                        }
                }
            }
        }
        return $data;
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
            $data[$term] = array_values(array_map('unserialize', array_unique(array_map(function ($v) {
                return serialize($v->jsonSerialize());
            }, $propertyData['values']))));
        }
        $options = [];
        // $options['isPartial'] = true;
        // $options['collectionAction'] = 'append';
        $options['isPartial'] = false;
        $options['collectionAction'] = 'replace';
        $response = $this->api->update($this->resourceType, $representation->id(), $data, [], $options);
    }

    /**
     * Check options used to import.
     *
     * @todo Mix with check in Import and make it available for external query.
     *
     * @param array $options Associative array of options.
     */
    protected function checkOptions(array $options)
    {
        extract($options);

        $allowedActions = [
            self::ACTION_CREATE,
            self::ACTION_APPEND,
            self::ACTION_REVISE,
            self::ACTION_UPDATE,
            self::ACTION_REPLACE,
            self::ACTION_DELETE,
            self::ACTION_SKIP,
        ];
        if (!in_array($action, $allowedActions)) {
            $this->hasErr = true;
            $this->logger->err(sprintf('Unknown action "%s".', $action));
        }

        // Specific check when a identifier is required.
        elseif (!in_array($action, [self::ACTION_CREATE, self::ACTION_SKIP])) {
            if (empty($identifierProperty)) {
                $this->hasErr = true;
                $this->logger->err(sprintf('The action "%s" requires a resource identifier property.', $action)); // @translate
            }
            if ($action !== self::ACTION_DELETE && !in_array($this->resourceType, ['item_sets', 'items', 'media'])) {
                $this->hasErr = true;
                $this->logger->err(sprintf('The action "%s" is not available for resource type "%s" currently.', // @translate
                    $action, $this->resourceType));
            }
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
