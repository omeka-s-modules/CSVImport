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
     * Process a row from the CSV file
     *
     * @param array $row
     * @return array $entityJson including the added data
     */
    public function processRow(array $row);
}
