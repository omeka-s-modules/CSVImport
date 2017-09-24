<?php
namespace CSVImportTest\Mvc\Controller\Plugin;

use CSVImport\CsvFile;
use OmekaTestHelper\Controller\OmekaControllerTestCase;

class CsvFileTest extends OmekaControllerTestCase
{
    protected $config;
    protected $basepath;

    protected $csvFile;

    public function setUp()
    {
        parent::setup();

        $services = $this->getServiceLocator();
        $this->config = $services->get('Config');

        $this->basepath = __DIR__ . '/_files/';

        $this->loginAsAdmin();
    }

    public function tearDown()
    {
        $this->csvFile->delete();
    }

    public function csvFileProvider()
    {
        return [
            ['test.csv', [], [true, 4, ['title', 'creator', 'description', 'tags', 'file']]],
            ['test_automap_columns_to_elements.csv', [], [true, 4, ['Dublin Core:Title', 'dcterms:creator', 'Description', 'Tags', 'Media url']]],
            ['test_cyrillic.csv', [], [false, 2, ['Dublin Core:Identifier', 'Collection', 'Dublin Core:Title', 'Dublin Core:Creator', 'Dublin Core:Date']]],
            ['empty.csv', [], [true, 1, ['']]],
            ['empty_really.csv', [], [true, 0, null]],
        ];
    }

    /**
     * @dataProvider csvFileProvider
     */
    public function testIsUtf8($filepath, $options, $expected)
    {
        $csvFile = $this->getCsvFile($filepath, $options);
        $this->assertEquals($expected[0], $csvFile->isUtf8());
    }

    /**
     * @dataProvider csvFileProvider
     */
    public function testCountRows($filepath, $options, $expected)
    {
        $csvFile = $this->getCsvFile($filepath, $options);
        $this->assertEquals($expected[1], $csvFile->countRows());
    }

    /**
     * @dataProvider csvFileProvider
     */
    public function testGetHeaders($filepath, $options, $expected)
    {
        $csvFile = $this->getCsvFile($filepath, $options);
        $this->assertEquals($expected[2], $csvFile->getHeaders());
    }

    protected function getCsvFile($filepath, array $options)
    {
        $filepath = $this->basepath . $filepath;

        if (empty($options)) {
            $options = [',', '"', '\\'];
        }
        list($delimiter, $enclosure, $escape) = $options;

        $csvFile = new CsvFile($this->config);
        $csvFile->setDelimiter($delimiter);
        $csvFile->setEnclosure($enclosure);
        $csvFile->setEscape($escape);
        $csvPath = $csvFile->getTempPath();
        copy($filepath, $csvPath);
        $csvFile->loadFromTempPath();

        $this->csvFile = $csvFile;
        return $csvFile;
    }
}
