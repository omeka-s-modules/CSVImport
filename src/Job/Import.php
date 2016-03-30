<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;
use Omeka\Job\Exception as JobException;
use CSVImport\CsvFile;

class Import extends AbstractJob
{
    protected $api;

    protected $csvFile;

    protected $addedCount;

    protected $columnMap;

    protected $fileMap;
    
    protected $uriMap;
    
    protected $multivalueMap;
    
    protected $multivalueSeparator; 
    
    protected $logger;

    public function perform()
    {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $config = $this->getServiceLocator()->get('Config');
        $mappingClasses = $config['csv_import_mappings'];
        $mappings = [];
        $args = $this->job->getArgs();
        foreach ($mappingClasses as $mappingClass) {
            $mappings[] = new $mappingClass($args, $this->logger);
        }
        
        $csvFile = new CsvFile($this->getServiceLocator());
        $csvFile->setTempPath($this->getArg('csvpath'));
        $csvFile->loadFromTempPath();
        $this->csvFile = $csvFile;
        $this->columnMap = $this->getArg('columnMap');
        $this->fileMap = $this->getArg('fileMap');
        $this->uriMap = $this->getArg('uriMap');
        $this->multivalueMap = $this->getArg('multivalueMap');
        $this->multivalueSeparator = $this->getArg('multivalueSeparator');
        
        
        
        $insertJson = [];
        foreach($this->csvFile->fileObject as $index => $row) {
            //skip the first (header) row, and any blank ones
            if ($index == 0 || empty($row)) {
                continue;
            }
            
            $itemJson = [];
            foreach($mappings as $mapping) {
                $itemJson = array_merge($itemJson, $mapping->processRow($row));
            }
            
            //$itemJson = $this->buildItemJson($row);
            $insertJson[] = $itemJson;
            //only add every X for batch import
            
            if ( $index % 20 == 0 ) {
                //batch create
                $this->createItems($insertJson);
                $insertJson = [];
            }
            
        }
        $this->createItems($insertJson);
    }

    protected function buildItemJson($row)
    {
        $itemJson = [];
        //add item-based data here, either from global settings
        //or from row-based mapping
        $itemSets = $this->getArg('itemSets', array());
        $itemJson['o:item_set'] = array();
        foreach($itemSets as $itemSetId) {
            $itemJson['o:item_set'][] = array('o:id' => $itemSetId);
        }
        $itemJson = array_merge($itemJson, $this->buildPropertyJson($row));
        $itemJson = array_merge($itemJson, $this->buildMediaJson($row));
        //let modules add to item json here?
        return $itemJson;
    }
    
    protected function buildPropertyJson($row)
    {
        $propertyJson = [];
        foreach($row as $index => $values) {
            $type = in_array($index, $this->uriMap) ? 'uri' : 'literal';
            if(isset($this->columnMap[$index])) {
                foreach($this->columnMap[$index] as $propertyId) {
                    if(in_array($index, $this->multivalueMap)) {
                        $multivalues = explode($this->multivalueSeparator, $values);
                        foreach($multivalues as $value) {
                            if ($type == 'uri') {
                                $propertyJson[$propertyId][] = array(
                                        '@id'         => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                );
                            } else {
                                $propertyJson[$propertyId][] = array(
                                        '@value'      => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                );
                            }
                        }
                    } else {
                        if ($type == 'uri') {
                            $propertyJson[$propertyId][] = array(
                                    '@id'         => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                            );
                        } else {
                            $propertyJson[$propertyId][] = array(
                                    '@value'      => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                            );
                        }
                    }
                }
            }
        }
        return $propertyJson;
    }

    protected function buildMediaJson($row)
    {
        $mediaJson = array('o:media' => array());
        foreach($row as $index => $values) {
            //split $values into an array, so people can have more than one file
            //in the column
            $fileUrls = explode($this->multivalueSeparator, $values);
            if(in_array($index, $this->fileMap)) {
                foreach($fileUrls as $fileUrl) {
                    $fileJson = array(
                        'o:ingester'     => 'url',
                        'o:source'   => trim($fileUrl),
                        'ingest_url' => trim($fileUrl),
                    );
                    $mediaJson['o:media'][] = $fileJson;
                }
            }
        }

        return $mediaJson;
    }
    
    protected function createItems($toCreate) 
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), true);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $createImportRecordsJson = array();
        
        foreach($createContent as $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($resourceReference);
        }
        
        $createImportRecordResponse = $this->api->batchCreate('csvimport_records', $createImportRecordsJson, array(), true);
    }
    
    protected function buildImportRecordJson($resourceReference) 
    {
        $recordJson = array('o:job'     => ['o:id' => $this->job->getId()],
                            'o:item'    => ['o:id' => $resourceReference->id()],
                            );
        return $recordJson;
    }
}
