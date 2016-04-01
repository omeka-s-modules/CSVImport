<?php
namespace CSVImport\Mapping;


class ItemMapping
{
    
    protected $args;
    
    protected $logger;
    
    public function __construct($args, $logger)
    {
        $this->args = $args;
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
        $itemJson['o:item_set'] = array();
        $itemSets = $this->args['itemSet'];
        $multivalueSeparator = $this->args['multivalue-separator'];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $itemSetMap = isset($this->args['column-itemset-id']) ? array_keys($this->args['column-itemset-id']) : [];
        
        
        //first, pull in the global settings
        foreach($itemSets as $itemSetId) {
            $itemJson['o:item_set'][] = array('o:id' => $itemSetId);
        }
        
        foreach($row as $index => $values) {
            //maybe weird, but just assuming a split for ids for simplicity's sake
            //since a list of ids shouldn't have any weird separators
            $values = explode($multivalueSeparator, $values);
            if (in_array($index, $itemSetMap)) {
                foreach($values as $itemSetId) {
                    $itemJson['o:item_set'][] = array('o:id' => trim($itemSetId));
                }
            }
        }
        return $itemJson;
    }
}