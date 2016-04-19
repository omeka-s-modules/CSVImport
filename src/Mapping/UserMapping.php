<?php
namespace CSVImport\Mapping;

class UserMapping
{
    public static function getLabel()
    {
        return "Users Info";
    }
    
    /**
     * Return a name to use in the form to identify this mapping's components
     */
    public static function getName()
    {
        return 'users';
    }

    public static function getSidebar($view)
    {
        $html = "<div id='users-sidebar' class='sidebar always-open'>
                <legend>Users Info</legend>
                
                </div>
        ";
        return $html;
    }
    
    /**
     * Process a row from the CSV file
     * @param array $row
     * @param array $itemJson
     * @return array $itemJson including the added data
     */
    public function processRow($row, $itemJson = array())
    {
        
    }
}