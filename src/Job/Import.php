<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;
use Omeka\Job\Exception as JobException;

class Import extends AbstractJob
{
    protected $api;

    protected $csvFile;

    protected $addedCount;

    protected $columnMap;

    protected $fileMap;


    public function perform()
    {
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->csvFile = $this->getArg('csvFile');
        $this->columnMap = $this->getArg('columnMap');
        $this->fileMap = $this->getArg('fileMap');
        $insertJson = [];
        foreach($this->csvFile as $index => $row) {
            $itemJson = $this->buildPropertyJson($row);
            $itemJson = array_merge($itemJson, $this->buildMediaJson($fileUrls));
            $insertJson[] = $itemJson;
            if ($index % 50 == 0 ) {
                //batch create
                $this->createItems($insertJson);
                $insertJson = [];
            }
        }
    }

    protected function buildPropertyJson($row)
    {
        $propertyJson = [];
        foreach($row as $index => $values) {
            //handle the situation where there are multiple values in one cell
            
            
            if(isset($this->columnMap[$index])) {
                foreach($this->columnMap[$index] as $maps) {
                    foreach($maps as $propertyId) {
                        $propertyJson[$propertyId][] = array(
                                '@value'      => $values,
                                'property_id' => $propertyId,
                                'type'        => 'literal',
                        );
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
            $fileUrls = explode(',', $values);
            if(in_array($index, $this->fileMap)) {
                foreach($fileUrls as $fileUrl) {
                    $fileJson = array(
                        'o:ingester'     => 'url',
                        'o:source'   => $fileUrl,
                        'ingest_url' => $fileUrl,
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
    
    public function buildImportRecordJson($resourceReference) 
    {
        
    }
}
