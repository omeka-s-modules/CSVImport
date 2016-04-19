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
        $html = "<div id='users-sidebar' class='sidebar always-open flag'>
                    <legend>Users Info</legend>
                    <ul>
                        <li data-flag='column-user-email'>
                            <a href='#' class='button'><span>Email</span></a>
                        </li>
                        <li data-flag='column-user-displayname'>
                            <a href='#' class='button'><span>Display Name</span></a>
                        </li>
                        <li data-flag='column-user-role'>
                            <a href='#' class='button'><span>Role</span></a>
                        </li>
                    </ul>
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
    public function processRow($row)
    {
        
    }
}