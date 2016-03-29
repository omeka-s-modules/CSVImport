<?php
namespace CSVImport\Mapping;


class PropertyMapping
{
    public static function getLabel()
    {
        return "Map Properties";
    }
    
    public static function getName()
    {
        return 'property-selector';
    }
    
    public static function getSidebar($view)
    {
        return $view->propertySelector('Select property to map. Click a column heading as the target, then select the properties for it.', true);
    }
    
    public function processRow($row)
    {
        
    }
}
