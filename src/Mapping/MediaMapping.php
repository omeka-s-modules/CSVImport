<?php
namespace CSVImport\Mapping;


class MediaMapping
{
    
    public static function getLabel($view)
    {
        return "Media Import";
    }
    
    public static function getName()
    {
        return 'media';
    }
    
    public static function getSidebar($view)
    {
        return $view->mediaSidebar();

    }
    
    public function processRow($row)
    {
        
    }
}