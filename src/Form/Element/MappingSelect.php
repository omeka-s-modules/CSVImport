<?php
namespace CSVImport\Form\Element;

use Omeka\Api\Manager as ApiManager;

class MappingSelect extends \Laminas\Form\Element\Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }

    public function getValueOptions(): array
    {
        $valueOptions = [];
        $mappings = $this->apiManager->search("csvimport_mappings", [])->getContent();
        foreach ($mappings as $mapping) {
            $valueOptions[$mapping->id()] = $mapping->name();
        }
        return $valueOptions;
    }
}
