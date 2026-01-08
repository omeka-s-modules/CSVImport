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

class SaveMappingModel extends AbstractPlugin
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
     * Save mapping.
     */
    public function __invoke(array $args): bool
    {
        $shouldOverrideMapping = false;
        $mappingName = $args['mapping_name'];

        // $this->logger->debug('[CSVImport] Mapping model name.');
        // $this->logger->debug(json_encode($mappingName));

        $alreadyExistsContent = $this->api->search('csvimport_mapping_models', ['name' => $mappingName])->getContent();
        if (count($alreadyExistsContent) > 0) {
            if (!empty($args['override_mapping']))
            {
                $shouldOverrideMapping = true;
            }
            else
            {
                // $this->logger->debug('[CSVImport] Already existing mapping model.');
                // $this->logger->debug(json_encode($alreadyExistsContent));
                return false;
            }
        }

        if (empty($args['columns'])) {
            // We first need to read the file to get the column names
            // Because we need to remember the column names
            $filePath = $args['filepath'];
            $fileName = $args['filename'];

            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                // $this->logger->err(sprintf("[CSV Import]: File '%s' not found when saving mapping model.", $filePath)); // @translate
            }

            // Open the file for reading
            if (($handle = fopen($filePath, 'r')) !== false) {
                // Read the first line as CSV (header row)
                $args['columns'] = fgetcsv($handle);

                // Close file
                fclose($handle);

                // Output the column names
                if (!$args['columns']) {
                    // $this->logger->err(sprintf("[CSV Import]: Unable to read columns when saving mapping model.")); // @translate
                }
            } else {
                // $this->logger->err(sprintf("[CSV Import]: File '%s' could not be opened when saving mapping model.", $filePath)); // @translate
            }

            if (empty($args['columns'])) {
                // $this->logger->err(sprintf("[CSV Import]: Unable to get columns from file '%s'.", $filePath)); // @translate
            }
        }

        // $this->logger->debug(sprintf("[CSV Import] Column names: " . PHP_EOL . "%s" . PHP_EOL, json_encode($args["columns"])));

        // don't save irrelevant data
        unset($args['filename']);
        unset($args['filesize']);
        unset($args['filepath']);
        unset($args['media_type']);
        unset($args['resource_type']);
        unset($args['automap_check_names_alone']);
        unset($args['mapping_name']);
        unset($args['override_mapping']);

        // $this->logger->debug(sprintf('[CSV Import: Args to be saved my mapping model]' . PHP_EOL . '%s' . PHP_EOL, json_encode($args)));

        if ($shouldOverrideMapping) {
            $this->api->update('csvimport_mapping_models', ['name' => $mappingName], ['mapping' => json_encode($args)], [], ['isPartial' => true]);
        }
        else {
            $this->api->create('csvimport_mapping_models', ['mapping' => json_encode($args), 'name' => $mappingName]);            
        }
        
        return true;
    }
}
