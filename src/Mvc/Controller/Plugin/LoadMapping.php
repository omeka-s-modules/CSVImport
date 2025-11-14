<?php

/*
 * Copyright 2025 Biblibre
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
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Log\Logger;

class LoadMapping extends AbstractPlugin
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
     * @var Logger
     */
    protected $logger;

    /**
     * @param Connection $connection
     * @param ApiManager $apiManager
     * @param Logger $logger
     */
    public function __construct(Connection $connection, ApiManager $apiManager, Logger $logger)
    {
        $this->connection = $connection;
        $this->api = $apiManager;
        $this->logger = $logger;
    }

    /**
     *
     */
    public function __invoke(int $id, array $columns)
    {
        // Fetch the mapping
        $mapping = $this->api->read('csvimport_mappings', $id)->getContent();

        if (empty($mapping)) {
            // $this->logger()->debug(sprintf('No mapping with id %s found.', $id));
            return [];
        }

        $mappingValue = json_decode($mapping->mapping(), true);

        $originalColumns = $mappingValue['columns'];
        unset($mappingValue['columns']);    // so we don't iterate over it after like it's an actual mapping

        // $this->logger->debug(sprintf('Old columns : ' . PHP_EOL . '%s' . PHP_EOL . 'New columns:' . PHP_EOL . '%s' . PHP_EOL,
        // json_encode($originalColumns), json_encode($columns)));

        // Find the mapping between old column indexes and current columns, according to name matching
        $oldToNewColumn = [];
        if (!empty($columns)) {
            foreach ($columns as $index => $column) {
                $columnMapping = array_search($column, $originalColumns);

                if ($columnMapping !== false) {
                    $oldToNewColumn[strval($columnMapping)] = $index;
                }
            }
        } else {
            foreach ($originalColumns as $index => $column) {
                $oldToNewColumn[strval($index)] = $index;
            }
        }

        // $this->logger->debug(sprintf('Column mapping: ' . PHP_EOL . '%s' . PHP_EOL, json_encode($oldToNewColumn)));
        // $this->logger->debug(sprintf('Mapping in : ' . PHP_EOL . '%s' . PHP_EOL, json_encode($mappingValue)));

        $automap = [];

        // if no columns passed as argument it means there are no new columns, so we remind of the original ones
        // used in MappingController
        if (empty($columns)) {
            $automap['columns'] = $originalColumns;
        }

        /*
         * Reading each entry that was sent by the user in the form
         * The structure is complicated and undocumented.
         */
        foreach ($mappingValue as $mappingValueColumnName => $mappingValueColumn) {
            $name = "";
            if (str_contains($mappingValueColumnName, 'column-')) {
                $name = explode('column-', $mappingValueColumnName)[1];

                // properties of type property
                if ($name == 'property') {
                    foreach ($mappingValueColumn as $index => $property) {
                        if (array_key_exists($index, $oldToNewColumn)) {
                            foreach ($property as $subindex => $subproperty) {
                                $element = [];
                                $element["name"] = $name;
                                $element["class"] = $name;
                                $element["multiple"] = true;
                                $element["special"] = " data-property-id=\"" . $subproperty . "\"";
                                $element["label"] = explode(':', $subindex)[1] ?? $subindex;
                                $element["value"] = $subproperty;
                                $automap[$oldToNewColumn[$index]][] = $element;
                            }
                        }
                    }
                }

                // these are column options. Only one per column, for data simplicity we put it in $automap[ColumnIndex][0]
                elseif ($name == "data-type"
                || $name == "multivalue"
                || $name == "language"
                || $name == "private-values"
                || $name == "resource-identifier-property") {
                    foreach ($mappingValueColumn as $index => $property) {
                        if (array_key_exists($index, $oldToNewColumn)) {
                            $automap[$oldToNewColumn[$index]][0][$name] = $property;
                        }
                    }
                }

                // these are mappings that are other than properties
                else {
                    foreach ($mappingValueColumn as $index => $property) {
                        if (array_key_exists($index, $oldToNewColumn)) {
                            $element = [];
                            $element["name"] = $name;
                            $element["class"] = $name;
                            $element["multiple"] = false;
                            $element["special"] = "";
                            $element["value"] = $property;
                            $element["label"] = "label"; // @tdodo
                            $automap[$oldToNewColumn[$index]][] = $element;
                        }
                    }
                }
            }
        }

        return $automap;
    }
}
