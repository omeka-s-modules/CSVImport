<?php
namespace CSVImport\Mapping;

class UserMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return "Users info";
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
        return $view->partial('common/user-sidebar');
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

        foreach ($row as $index => $value) {
            switch ($index) {
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
