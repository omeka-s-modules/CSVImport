<?php
namespace CSVImportTest\Source;

use CSVImport\Source\OpenDocumentSpreadsheet;

if (!class_exists('CSVImportTest\Source\AbstractSource')) {
    require __DIR__ . '/AbstractSource.php';
}

class OpenDocumentSpreadsheetTest extends AbstractSource
{
    protected $sourceClass = OpenDocumentSpreadsheet::class;

    public function sourceProvider()
    {
        return [
            ['test_resources_heritage.ods', [], [true, 21, ['Identifier', 'Resource Type',
                'Collection Identifier', 'Item Identifier', 'Media Url', 'Resource class', 'Title',
                'Dublin Core : Creator', 'Date', 'Rights', 'Description', 'Dublin Core:Format',
                'Dublin Core : Spatial Coverage', 'Tags', 'Latitude', 'Longitude', 'Default Zoom',]]],
        ];
    }
}
