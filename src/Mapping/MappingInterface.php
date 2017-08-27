<?php
namespace CSVImport\Mapping;

interface MappingInterface
{
    /**
     * Return a label for the button on the column mapping form
     */
    public static function getLabel();

    /**
     * Return a name to use in the form to identify this mapping's components
     */
    public static function getName();

    /**
     * Return the HTML for the sidebar for setting mappings
     * Must be a <div id='$name' class='sidebar'>
     * @param Zend\View\Renderer\PHPRenderer $view
     */
    public static function getSidebar($view);

    /**
     * Process a row from the CSV file
     * @param array $row
     * @return array $entityJson including the added data
     */
    public function processRow($row);
}
