<?php
namespace CSVImportTest\Source;

use CSVImport\Source\CsvFile;
use CSVImportTest\Source\AbstractSource;

if (!class_exists('CSVImportTest\Source\AbstractSource')) {
    require __DIR__ . '/AbstractSource.php';
}

class CsvFileTest extends AbstractSource
{
    protected $sourceClass = CsvFile::class;

    public function sourceProvider()
    {
        return [
            ['test.csv', [], [true, 4, ['title', 'creator', 'description', 'tags', 'file']]],
            ['test_automap_columns.csv', [], [true, 4, [
                'Identifier', 'Dublin Core:Title', 'dcterms:creator', 'Description', 'Date', 'Publisher',
                'Collections', 'Tags', 'Resource template', 'Resource class',
                'Media url',
            ]]],
            ['test_cyrillic.csv', [], [false, 2, ['Dublin Core:Identifier', 'Collection', 'Dublin Core:Title', 'Dublin Core:Creator', 'Dublin Core:Date']]],
            ['empty.csv', [], [true, 1, ['']]],
            ['empty_really.csv', [], [true, 0, null]],
        ];
    }
}
