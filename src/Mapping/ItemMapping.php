<?php
namespace CSVImport\Mapping;

class ItemMapping
{

    protected $args;

    protected $api;

    protected $logger;
    
    protected $serviceLocator;

    public function __construct($args, $serviceLocator)
    {
        $this->args = $args;
        $this->logger = $serviceLocator->get('Omeka\Logger');
        $this->api = $serviceLocator->get('Omeka\ApiManager');
        $this->serviceLocator = $serviceLocator;
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
        return $view->itemSidebar();
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

        if (isset($this->args['o:owner'])) {
            $ownerId = $this->args['o:owner'];
            $itemJson['o:owner'] = ['o:id' => $ownerId];
        }
        
        $multivalueSeparator = $this->args['multivalue-separator'];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $itemSetMap = isset($this->args['column-itemset-id']) ? array_keys($this->args['column-itemset-id']) : [];
        $resourceTemplateMap = isset($this->args['column-resourcetemplate']) ? array_keys($this->args['column-resourcetemplate']) : [];
        $resourceClassMap = isset($this->args['column-resourceclass']) ? array_keys($this->args['column-resourceclass']) : [];
        $ownerMap = isset($this->args['column-owneremail']) ? array_keys($this->args['column-owneremail']) : [];

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
                    $itemJson['o:resource_template'] = ['o:id' => $resourceTemplate->id()];
                }
            }
            if (in_array($index, $resourceClassMap)) {
                $resourceClass = $this->findResourceClass($values[0]);
                if ($resourceClass) {
                    $itemJson['o:resource_class'] = ['o:id' => $resourceClass->id()];
                }
            }
            if (in_array($index, $ownerMap)) {
                $user = $this->findUser($values[0]);
                if ($user) {
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
        $class = $content[0];
        $classTerm = $class->term();
        $this->logger->debug($classTerm);
        if ($term != $classTerm) {
            $this->logger->err("$term is not a valid resource class. Resource Classes must be a Class found on the Vocabularies page.");
            return false;
        }
        return $content[0];
    }

    protected function findResourceTemplate($label)
    {
        $label = trim($label);
        $response = $this->api->search('resource_templates', array('label' => $label));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }

    protected function findUser($email)
    {
        $email = trim($email);
        $response = $this->api->search('users', array('email' => $email));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
}
