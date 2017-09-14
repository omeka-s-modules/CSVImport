<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

class UserMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return 'User info'; // @translate
    }

    public static function getName()
    {
        return 'user-data';
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->partial('common/user-sidebar');
    }

    /**
     * Process a row from the CSV file.
     *
     * @param array $row
     * @return array $itemJson including the added data
     */
    public function processRow(array $row)
    {
        $userJson = [];

        $emailIndex = array_keys($this->args['column-user_email'])[0];
        $nameIndex = array_keys($this->args['column-user_name'])[0];
        $roleIndex = array_keys($this->args['column-user_role'])[0];

        foreach ($row as $index => $value) {
            switch ($index) {
                case $emailIndex:
                    $userJson['o:email'] = $value;
                break;

                case $nameIndex:
                    $userJson['o:name'] = $value;
                break;

                case $roleIndex:
                    $userJson['o:role'] = $value;
                break;
            }
        }

        if (empty($userJson['o:name'])) {
            $userJson['o:name'] = $userJson['o:email'];
        }
        return $userJson;
    }
}
