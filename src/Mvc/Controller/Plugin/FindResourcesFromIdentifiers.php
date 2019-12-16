<?php

/*
 * Copyright 2017-2019 Daniel Berthereau
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace CSVImport\Mvc\Controller\Plugin;

use Doctrine\DBAL\Connection;
use Omeka\Api\Manager as ApiManager;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class FindResourcesFromIdentifiers extends AbstractPlugin
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @param Connection $connection
     * @param ApiManager $apiManager
     */
    public function __construct(Connection $connection, ApiManager $apiManager)
    {
        $this->connection = $connection;
        $this->api = $apiManager;
    }

    /**
     * Find a list of resource ids from a list of identifiers (or one id).
     *
     * When there are true duplicates and case insensitive duplicates, the first
     * case sensitive is returned, else the first case insensitive resource.
     *
     * All identifiers are returned, even without id.
     *
     * @todo Manage Media source html.
     *
     * @param array|string $identifiers Identifiers should be unique. If a
     * string is sent, the result will be the resource.
     * @param string|int|array $identifierName Property as integer or term,
     * "o:id", a media ingester (url or file), or an associative array with
     * multiple conditions (for media source). May be a list of identifier
     * metadata names, in which case the identifiers are searched in a list of
     * properties and/or in internal ids.
     * @param string $resourceType The resource type if any.
     * @return array|int|null|Object Associative array with the identifiers as key
     * and the ids or null as value. Order is kept, but duplicate identifiers
     * are removed. If $identifiers is a string, return directly the resource
     * id, or null.
     */
    public function __invoke($identifiers, $identifierName, $resourceType = null)
    {
        $isSingle = !is_array($identifiers);

        if (empty($identifierName)) {
            return $isSingle ? null : [];
        }

        if ($isSingle) {
            $identifiers = [$identifiers];
        }
        $identifiers = array_unique(array_filter(array_map(function ($v) {
            return trim($v);
        }, $identifiers)));
        if (empty($identifiers)) {
            return $isSingle ? null : [];
        }

        $args = $this->normalizeArgs($identifierName, $resourceType);
        if (empty($args)) {
            return $isSingle ? null : [];
        }
        list($identifierTypeNames, $resourceType, $itemId) = $args;

        foreach ($identifierTypeNames as $identifierType => $identifierName) {
            $result = $this->findResources($identifierType, $identifiers, $identifierName, $resourceType, $itemId);
            if (empty($result)) {
                continue;
            }
            return $isSingle ? reset($result) : $result;
        }
        return $isSingle ? null : [];
    }

    protected function findResources($identifierType, array $identifiers, $identifierName, $resourceType, $itemId)
    {
        switch ($identifierType) {
            case 'o:id':
                return $this->findResourcesFromInternalIds($identifiers, $resourceType);
            case 'property':
                if (!is_array($identifierName)) {
                    $identifierName = [$identifierName];
                }
                return $this->findResourcesFromPropertyIds($identifiers, $identifierName, $resourceType);
            case 'media_source':
                if (is_array($identifierName)) {
                    $identifierName = reset($identifierName);
                }
                return $this->findResourcesFromMediaSource($identifiers, $identifierName, $itemId);
        }
    }

    protected function normalizeArgs($identifierName, $resourceType)
    {
        $identifierType = null;
        $identifierTypeName = null;
        $itemId = null;

        // Process identifier metadata names as an array.
        if (is_array($identifierName)) {
            if (isset($identifierName['o:ingester'])) {
                // TODO Currently, the media source cannot be html.
                if ($identifierName['o:ingester'] === 'html') {
                    return null;
                }
                $identifierType = 'media_source';
                $identifierTypeName = $identifierName['o:ingester'];
                $resourceType = 'media';
                $itemId = empty($identifierName['o:item']['o:id']) ? null : $identifierName['o:item']['o:id'];
            } else {
                return $this->normalizeMultipleIdentifierMetadata($identifierName, $resourceType);
            }
        }
        // Next, identifierName is a string or an integer.
        elseif ($identifierName === 'o:id') {
            $identifierType = 'o:id';
            $identifierTypeName = 'o:id';
        } elseif (is_numeric($identifierName)) {
            $identifierType = 'property';
            // No check of the property id for quicker process.
            $identifierTypeName = (int) $identifierName;
        } elseif (in_array($identifierName, ['url', 'file'])) {
            $identifierType = 'media_source';
            $identifierTypeName = $identifierName;
            $resourceType = 'media';
            $itemId = null;
        } else {
            $properties = $this->api
                ->search('properties', ['term' => $identifierName])->getContent();
            if ($properties) {
                $identifierType = 'property';
                $identifierTypeName = $properties[0]->id();
            }
        }

        if (empty($identifierTypeName)) {
            return null;
        }

        if ($resourceType) {
            $resourceType = $this->normalizeResourceType($resourceType);
            if (is_null($resourceType)) {
                return null;
            }
        }

        return [
            [$identifierType => $identifierTypeName],
            $resourceType,
            $itemId,
        ];
    }

    protected function normalizeMultipleIdentifierMetadata($identifierNames, $resourceType)
    {
        $identifierTypeNames = [];
        foreach ($identifierNames as $identifierName) {
            $args = $this->normalizeArgs($identifierName, $resourceType);
            if ($args) {
                list($identifierTypeName) = $args;
                $identifierName = reset($identifierTypeName);
                $identifierType = key($identifierTypeName);
                switch ($identifierType) {
                    case 'o:id':
                    case 'media_source':
                        $identifierTypeNames[$identifierType] = $identifierName;
                        break;
                    default:
                        $identifierTypeNames[$identifierType][] = $identifierName;
                        break;
                }
            }
        }
        if (!$identifierTypeNames) {
            return null;
        }

        if ($resourceType) {
            $resourceType = $this->normalizeResourceType($resourceType);
            if (is_null($resourceType)) {
                return null;
            }
        }

        return [
            $identifierTypeNames,
            $resourceType,
            null,
        ];
    }

    protected function normalizeResourceType($resourceType)
    {
        $resourceTypes = [
            'items' => \Omeka\Entity\Item::class,
            'item_sets' => \Omeka\Entity\ItemSet::class,
            'media' => \Omeka\Entity\Media::class,
            'resources' => '',
            'resource' => '',
            'resource:item' => \Omeka\Entity\Item::class,
            'resource:itemset' => \Omeka\Entity\ItemSet::class,
            'resource:media' => \Omeka\Entity\Media::class,
            // Avoid a check and make the plugin more flexible.
            \Omeka\Entity\Item::class => \Omeka\Entity\Item::class,
            \Omeka\Entity\ItemSet::class => \Omeka\Entity\ItemSet::class,
            \Omeka\Entity\Media::class => \Omeka\Entity\Media::class,
            \Omeka\Entity\Resource::class => '',
            'o:item' => \Omeka\Entity\Item::class,
            'o:item_set' => \Omeka\Entity\ItemSet::class,
            'o:media' => \Omeka\Entity\Media::class,
            // Other resource types.
            'item' => \Omeka\Entity\Item::class,
            'item_set' => \Omeka\Entity\ItemSet::class,
            'item-set' => \Omeka\Entity\ItemSet::class,
            'itemset' => \Omeka\Entity\ItemSet::class,
            'resource:item_set' => \Omeka\Entity\ItemSet::class,
            'resource:item-set' => \Omeka\Entity\ItemSet::class,
        ];
        return isset($resourceTypes[$resourceType])
            ? $resourceTypes[$resourceType]
            : null;
    }

    protected function findResourcesFromInternalIds(array $ids, $resourceType)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return [];
        }

        // The api manager doesn't manage this type of search.
        $conn = $this->connection;

        $qb = $conn->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('resource.id')
            ->from('resource', 'resource')
            ->addOrderBy('resource.id', 'ASC');

        $parameters = [];
        if (count($ids) === 1) {
            $qb
                ->andWhere($expr->eq('resource.id', ':id'));
            $parameters['id'] = reset($ids);
        } else {
            // Warning: there is a difference between qb / dbal and qb / orm for
            // "in" in qb, when a placeholder is used, there should be one
            // placeholder for each value for expr->in().
            $placeholders = [];
            foreach (array_values($ids) as $key => $value) {
                $placeholder = 'id_' . $key;
                $parameters[$placeholder] = $value;
                $placeholders[] = ':' . $placeholder;
            }
            $qb
                ->andWhere($expr->in('resource.id', $placeholders));
        }

        if ($resourceType) {
            $qb
                ->andWhere($expr->eq('resource.resource_type', ':resource_type'));
            $parameters['resource_type'] = $resourceType;
        }

        $qb
            ->setParameters($parameters);

        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Reorder the result according to the input (simpler in php and there
        // is no duplicated identifiers).
        return array_replace(array_fill_keys($ids, null), array_combine($result, $result));
    }

    protected function findResourcesFromPropertyIds(array $identifiers, array $propertyIds, $resourceType)
    {
        // The api manager doesn't manage this type of search.
        $conn = $this->connection;

        $qb = $conn->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('value.value AS identifier', 'value.resource_id AS id')
            ->from('value', 'value')
            ->leftJoin('value', 'resource', 'resource', 'value.resource_id = resource.id')
            // ->andWhere($expr->in('value.property_id', $propertyIds))
            // ->andWhere($expr->in('value.value', $identifiers))
            ->addOrderBy('resource.id', 'ASC')
            ->addOrderBy('value.id', 'ASC');

        $parameters = [];
        if (count($identifiers) === 1) {
            $qb
                ->andWhere($expr->eq('value.value', ':identifier'));
            $parameters['identifier'] = reset($identifiers);
        } else {
            // Warning: there is a difference between qb / dbal and qb / orm for
            // "in" in qb, when a placeholder is used, there should be one
            // placeholder for each value for expr->in().
            $placeholders = [];
            foreach (array_values($identifiers) as $key => $value) {
                $placeholder = 'value_' . $key;
                $parameters[$placeholder] = $value;
                $placeholders[] = ':' . $placeholder;
            }
            $qb
                ->andWhere($expr->in('value.value', $placeholders));
        }

        if (count($propertyIds) === 1) {
            $qb
                ->andWhere($expr->eq('value.property_id', ':property_id'));
            $parameters['property_id'] = reset($propertyIds);
        } else {
            $placeholders = [];
            foreach (array_values($propertyIds) as $key => $value) {
                $placeholder = 'property_' . $key;
                $parameters[$placeholder] = $value;
                $placeholders[] = ':' . $placeholder;
            }
            $qb
                ->andWhere($expr->in('value.property_id', $placeholders));
        }

        if ($resourceType) {
            $qb
                ->andWhere($expr->eq('resource.resource_type', ':resource_type'));
            $parameters['resource_type'] = $resourceType;
        }

        $qb
            ->setParameters($parameters);

        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        // $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) cannot be used, because it
        // replaces the first id by later ids in case of true duplicates.
        $result = $stmt->fetchAll();

        return $this->cleanResult($identifiers, $result);
    }

    protected function findResourcesFromMediaSource(array $identifiers, $ingesterName, $itemId = null)
    {
        // The api manager doesn't manage this type of search.
        $conn = $this->connection;

        $qb = $conn->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('media.source AS identifier', 'media.id AS id')
            ->from('media', 'media')
            ->andWhere('media.ingester = :ingester')
            // ->andWhere('media.source IN (' . implode(',', array_map([$conn, 'quote'], $identifiers)) . ')')
            ->addOrderBy('media.id', 'ASC');

        $parameters = [];
        $parameters['ingester'] = $ingesterName;

        if (count($identifiers) === 1) {
            $qb
                ->andWhere($expr->eq('media.source', ':identifier'));
            $parameters['identifier'] = reset($identifiers);
        } else {
            // Warning: there is a difference between qb / dbal and qb / orm for
            // "in" in qb, when a placeholder is used, there should be one
            // placeholder for each value for expr->in().
            $placeholders = [];
            foreach (array_values($identifiers) as $key => $value) {
                $placeholder = 'value_' . $key;
                $parameters[$placeholder] = $value;
                $placeholders[] = ':' . $placeholder;
            }
            $qb
                ->andWhere($expr->in('media.source', $placeholders));
        }

        if ($itemId) {
            $qb
                ->andWhere($expr->eq('media.item_id', ':item_id'));
            $parameters['item_id'] = $itemId;
        }

        $qb
            ->setParameters($parameters);

        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        // $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) cannot be used, because it
        // replaces the first id by later ids in case of true duplicates.
        $result = $stmt->fetchAll();

        return $this->cleanResult($identifiers, $result);
    }

    /**
     * Reorder the result according to the input (simpler in php and there is no
     * duplicated identifiers).
     *
     * @param array $identifiers
     * @param array $result
     * @return array
     */
    protected function cleanResult(array $identifiers, array $result)
    {
        $cleanedResult = array_fill_keys($identifiers, null);

        // Prepare the lowercase result one time only.
        $lowerResult = array_map(function ($v) {
            return ['identifier' => strtolower($v['identifier']), 'id' => $v['id']];
        }, $result);

        foreach ($cleanedResult as $key => $value) {
            // Look for the first case sensitive result.
            foreach ($result as $resultValue) {
                if ($resultValue['identifier'] == $key) {
                    $cleanedResult[$key] = $resultValue['id'];
                    continue 2;
                }
            }
            // Look for the first case insensitive result.
            $lowerKey = strtolower($key);
            foreach ($lowerResult as $lowerResultValue) {
                if ($lowerResultValue['identifier'] == $lowerKey) {
                    $cleanedResult[$key] = $lowerResultValue['id'];
                    continue 2;
                }
            }
        }

        return $cleanedResult;
    }
}
