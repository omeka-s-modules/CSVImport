<?php
namespace CSVImport\Mapping;


class ItemMapping
{
    
    protected $args;
    
    protected $api;
    
    protected $logger;
    
    public function __construct($args, $api, $logger)
    {
        $this->args = $args;
        $this->api = $api;
        $this->logger = $logger;
    }
    
    public static function getLabel()
    {
        return "Item Data";
    }
    
    public static function getName()
    {
        return 'item';
    }
    
    public static function getSidebar($view)
    {
        return "
        <div id='item-sidebar' class='sidebar flags'>
            <p>Data from this column applies to the entire item being imported.</p>
            <p>Settings here will override any corresponding settings in Basic Import Settings.</p>
            <ul>
                <li data-flag='column-itemset-id'>
                    <a href='#' class='button'><span>Item Set ID</span></a>
                </li>
                <li data-flag='column-resourcetemplate'>
                    <a href='#' class='button'><span>Resource Template Name</span></a>
                </li>
                <li data-flag='column-resourceclass'>
                    <a href='#' class='button'><span>Resource Class Term</span></a>
                </li>
                <li data-flag='column-owneremail'>
                    <a href='#' class='button'><span>Owner Email Address</span></a>
                </li>
            </ul>
        </div>
        ";
    }
    
    public function processRow($row)
    {
        $itemJson = [];

            
        //first, pull in the global settings
        if (isset($this->args['o:item_set'])) {
            $itemSets = $this->args['o:item_set'];
            $itemJson['o:item_set'] = [];
            foreach($itemSets as $itemSetId) {
                $itemJson['o:item_set'][] = array('o:id' => $itemSetId);
            }
        }
        if (isset($this->args['o:resource_class'])) {
            $resourceClass = $this->args['o:resource_class']['o:id'];
            $itemJson['o:resource_class'] = ['o:id' => $resourceClass];
        }
        if (isset($this->args['o:resource_template'])) {
            $resourceTemplate = $this->args['o:resource_template']['o:id'];
            $itemJson['o:resource_template'] = ['o:id' => $resourceTemplate];
        }
        
        $multivalueSeparator = $this->args['multivalue-separator'];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $itemSetMap = isset($this->args['column-itemset-id']) ? array_keys($this->args['column-itemset-id']) : [];
        
        $resourceTemplateMap = isset($this->args['column-resourcetemplate']) ? array_keys($this->args['column-resourcetemplate']) : [];
        $resourceClassMap = isset($this->args['column-resourceclass']) ? array_keys($this->args['column-resourceclass']) : [];
        $ownerMap = isset($this->args['column-owneremail']) ? array_keys($this->args['column-owneremail']) : [];

        $this->logger->debug(print_r($userMap, true));
        foreach($row as $index => $values) {
            //maybe weird, but just assuming a split for ids for simplicity's sake
            //since a list of ids shouldn't have any weird separators
            $values = explode($multivalueSeparator, $values);
            if (in_array($index, $itemSetMap)) {
                foreach($values as $itemSetId) {
                    $itemJson['o:item_set'][] = ['o:id' => trim($itemSetId)];
                }
            }
            if (in_array($index, $resourceTemplateMap)) {
                
                $resourceTemplate = $this->findResourceTemplate($values[0]);
                if ($resourceTemplate) {
                    $this->logger->debug('template label ' . $resourceTemplate->label());
                    $itemJson['o:resource_template'] = ['o:id' => $resourceTemplate->id()];
                }
            }
            if (in_array($index, $resourceClassMap)) {
                $resourceClass = $this->findResourceClass($values[0]);
                if ($resourceClass) {
                    $this->logger->debug('rc id ' . $resourceClass->id());
                    $itemJson['o:resource_class'] = ['o:id' => $resourceClass->id()];
                }
            }
            if (in_array($index, $ownerMap)) {
                $user = $this->findUser($values[0]);
                if ($user) {
                    $this->logger->debug('userid ' . $user->id());
                    $itemJson['o:owner'] = ['o:id' => $user->id()];
                }
            }
        }
        return $itemJson;
    }
    
    protected function findResourceClass($term)
    {
        $term = trim($term);
        $response = $this->api->search('resource_classes', array('term' => $term));
        $content = $response->getContent();
        $this->logger->debug('response count ' . count($response));
        if (empty($content)) {
            return false;
        }
        $c0 = $content[0];
        $this->logger->debug('c0 label ' . $c0->term());
        
        return $content[0];
    }
    
    protected function findResourceTemplate($label)
    {
        $label = trim($label);
        $this->logger->debug('template label ' . $label);
        $response = $this->api->search('resource_templates', array('label' => $label));
        $this->logger->debug('response count ' . count($response));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
    
    protected function findUser($email)
    {
        $email = trim($email);
        $this->logger->debug('user email ' . $email);
        $response = $this->api->search('users', array('email' => $email));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        $this->logger->debug('response count ' . count($response));
        return $content[0];
    }
}
