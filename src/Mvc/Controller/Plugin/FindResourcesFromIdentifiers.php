<?php

/*
 * Copyright 2017 Daniel Berthereau
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
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class FindResourcesFromIdentifiers extends AbstractPlugin
{
    /**
     * @var Connexion
     */
    protected $connexion;

    /**
     * @param Connection $connexion
     */
    public function __construct(Connection $connexion)
    {
        $this->connexion = $connexion;
    }

    /**
     * Find a list of resource ids from a list of identifiers.
     *
     * @param array|string $identifiers Identifiers should be unique. If a
     * string is sent, the result will be the resource.
     * @param string|int $identifierProperty
     * @param string $resourceType The resource type if any.
     * @return array|int|null Associative array with the identifiers as key and the ids
     * or null as value. Order is kept, but duplicate identifiers are removed.
     * If $identifiers is a string, return directly the resource id, or null.
     */
    public function __invoke($identifiers, $identifierProperty, $resourceType = null)
    {
        $isSingle = is_string($identifiers);
        if ($isSingle) {
            $identifiers = [$identifiers];
        }
        $identifiers = array_unique(array_filter(array_map('trim', $identifiers)));
        if (empty($identifiers)) {
            return $isSingle ? null : [];
        }

        $identifierProperty = $identifierProperty === 'internal_id'
            ? 'internal_id'
            : (int) $identifierProperty;
        if (empty($identifierProperty)) {
            return $isSingle ? null : [];
        }

        if (!empty($resourceType)) {
            $resourceTypes = [
                'item_sets' => 'Omeka\Entity\ItemSet',
                'items' => 'Omeka\Entity\Item',
                'media' => 'Omeka\Entity\Media',
                'resources' => '',
                // Avoid a check and make the plugin more flexible.
                'Omeka\Entity\ItemSet' => 'Omeka\Entity\ItemSet',
                'Omeka\Entity\Item' => 'Omeka\Entity\Item',
                'Omeka\Entity\Media' => 'Omeka\Entity\Media',
                'Omeka\Entity\Resource' => '',
            ];
            if (!isset($resourceTypes[$resourceType])) {
                return $isSingle ? null : [];
            }
            $resourceType = $resourceTypes[$resourceType];
        }

        switch ($identifierProperty) {
            case 'internal_id':
                $result = $this->findResourcesFromInternalIds($identifiers, $identifierProperty, $resourceType);
                break;
            default:
                $result = $this->findResourcesFromPropertyIds($identifiers, $identifierProperty, $resourceType);
                break;
        }

        return $isSingle ? ($result ? reset($result) : null) : $result;
    }

    protected function findResourcesFromPropertyIds($identifiers, $identifierProperty, $resourceType)
    {
        // The api manager doesn't manage this type of search.
        $conn = $this->connexion;

        // Search in multiple resource types in one time.
        $quotedIdentifiers = array_map([$conn, 'quote'], $identifiers);
        $quotedIdentifiers = implode(',', $quotedIdentifiers);
        $qb = $conn->createQueryBuilder()
            ->select('value.value', 'value.resource_id')
            ->from('value', 'value')
            ->leftJoin('value', 'resource', 'resource', 'value.resource_id = resource.id')
            ->andwhere('value.property_id = :property_id')
            ->setParameter(':property_id', $identifierProperty)
            // ->andWhere('value.value in (:values)')
            // ->setParameter(':values', $identifiers)
            ->andWhere("value.value in ($quotedIdentifiers)")
            ->addOrderBy('resource.id', 'ASC')
            ->addOrderBy('value.id', 'ASC');
        if ($resourceType) {
            $qb
                ->andWhere('resource.resource_type = :resource_type')
                ->setParameter(':resource_type', $resourceType);
        }
        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        $result = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Reorder the result according to the input (simpler in php and there
        // is no duplicated identifiers).
        return array_replace(array_fill_keys($identifiers, null), $result);
    }

    protected function findResourcesFromInternalIds($identifiers, $identifierProperty, $resourceType)
    {
        // The api manager doesn't manage this type of search.
        $conn = $this->connexion;
        $identifiers = array_map('intval', $identifiers);
        $quotedIdentifiers = implode(',', $identifiers);
        $qb = $conn->createQueryBuilder()
            ->select('resource.id')
            ->from('resource', 'resource')
            // ->andWhere('resource.id in (:ids)')
            // ->setParameter(':ids', $identifiers)
            ->andWhere("resource.id in ($quotedIdentifiers)")
            ->addOrderBy('resource.id', 'ASC');
        if ($resourceType) {
            $qb
                ->andWhere('resource.resource_type = :resource_type')
                ->setParameter(':resource_type', $resourceType);
        }
        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Reorder the result according to the input (simpler in php and there
        // is no duplicated identifiers).
        return array_replace(array_fill_keys($identifiers, null), $result);
    }
}
