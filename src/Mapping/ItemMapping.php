<?php
namespace CSVImport\Mapping;


class ItemMapping
{
    
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
        
    }
}