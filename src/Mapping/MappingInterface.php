<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

interface MappingInterface
{
    /**
     * Return a label for the button on the column mapping form
     *
     * @return string
     */
    public static function getLabel();

    /**
     * Return a name to use in the form to identify this mapping's components
     *
     * @return string
     */
    public static function getName();

    /**
     * Return the HTML for the sidebar for setting mappings
     * Must be a <div id='$name' class='sidebar'>
     *
     * @param PHPRenderer $view
     * @return string
     */
    public static function getSidebar(PhpRenderer $view);


    /**
     * Define if the current row has an error.
     *
     * @return bool
     */
    public function getHasErr();

    /**
     * Process a row from the CSV file.
     *
     * Note: the empty values should be set too in the returned data in order to
     * manage some updates.
     *
     * @param array $row
     * @return array $entityJson including the added data
     */
    public function processRow(array $row);
}
