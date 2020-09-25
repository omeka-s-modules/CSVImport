<?php
namespace CSVImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

class UserMapping extends AbstractMapping
{
    protected $label = 'User info'; // @translate
    protected $name = 'user-data';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/user');
    }

    /**
     * Process a row from the CSV file.
     *
     * @param array $row
     * @return array $itemJson including the added data
     */
    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
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
