<?php
namespace CSVImport\Mapping;

class UserMapping
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
        $html = "<div id='users-sidebar' class='sidebar always-open flags'>
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
        $emailIndex = array_keys($this->args['column-user-email'])[0];
        $nameIndex = array_keys($this->args['column-user-displayname'])[0];
        $roleIndex = array_keys($this->args['column-user-role'])[0];
        $userJson = [];
        
        foreach($row as $index => $value) {
            switch($index) {
                case $emailIndex:
                    $userJson['o:email'] = trim($value);
                break;
                
                case $nameIndex:
                    $userJson['o:name'] = trim($value);
                break;
                
                case $roleIndex:
                    $userJson['o:role'] = trim($value);
                break;
            }
        }
        
        if (empty($userJson['o:name'])) {
            $userJson['o:name'] = $userJson['o:email'];
        }
        return $userJson;
    }
}
