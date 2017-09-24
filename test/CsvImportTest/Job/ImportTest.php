<?php
namespace CSVImportTest\Mvc\Controller\Plugin;

use OmekaTestHelper\Controller\OmekaControllerTestCase;
use CSVImport\Job\Import;
use Omeka\Entity\Job;

class ImportTest extends OmekaControllerTestCase
{
    protected $entityManager;
    protected $auth;
    protected $api;
    protected $basepath;

    protected $tempfile;

    public function setUp()
    {
        parent::setup();

        $services = $this->getServiceLocator();
        $this->entityManager = $services->get('Omeka\EntityManager');
        $this->auth = $services->get('Omeka\AuthenticationService');
        $this->api = $services->get('Omeka\ApiManager');
        $this->basepath = __DIR__ . '/../_files/';

        $this->loginAsAdmin();

        $this->tempfile = tempnam(sys_get_temp_dir(), 'omk');
    }

    public function tearDown()
    {
        if (file_exists($this->tempfile)) {
            unlink($this->tempfile);
        }
    }

    public function csvFileProvider()
    {
        return [
            ['test.csv', ['items' => 3, 'media' => 4]],
        ];
    }

    /**
     * @dataProvider csvFileProvider
     */
    public function testPerformCreate($filepath, $totals)
    {
        $filepath = $this->basepath . $filepath;
        $filebase = substr($filepath, 0, -4);

        $this->performProcessForFile($filepath);

        foreach ($totals as $resourceType => $total) {
            $result = $this->api->search($resourceType)->getContent();
            $this->assertEquals($total, count($result));
            foreach ($result as $key => $resource) {
                $expectedFile = $filebase . '.' . $resourceType . '-' . ($key + 1) . '.' . 'api.json';
                if (!file_exists($expectedFile)) {
                    continue;
                }
                $expected = file_get_contents($expectedFile);
                $expected = $this->cleanApiResult(json_decode($expected, true));
                $resource = $this->cleanApiResult($resource->getJsonLd());
                $this->assertNotEmpty($resource);
                $this->assertEquals($expected, $resource);
            }
        }
    }

    /**
     * This false test allows to prepare a list of resources and to use them in
     * dependencies for performance reasons.
     *
     * @return array
     */
    public function testPerformCreateOne()
    {
        $filepath = 'test.csv';
        $totals = ['items' => 3, 'media' => 4];
        $filepath = $this->basepath . $filepath;

        $this->performProcessForFile($filepath);
        $this->assertTrue(true);

        $resources = [];
        foreach ($totals as $resourceType => $total) {
            $result = $this->api->search($resourceType)->getContent();
            foreach ($result as $key => $resource) {
                $resources[$resourceType][$key + 1] = $resource;
            }
        }
        return $resources;
    }

    public function csvFileUpdateProvider()
    {
        return [
            ['test_skip.csv', ['items', 1]],
            ['test_update_replace_a.csv', ['items', 1]],
            ['test_update_replace_b.csv', ['items', 1]],
        ];
    }

    /**
     * @dataProvider csvFileUpdateProvider
     * @depends testPerformCreateOne
     */
    public function testPerformUpdate($filepath, $options, $resources)
    {
        $filepath = $this->basepath . $filepath;
        $filebase = substr($filepath, 0, -4);
        list($resourceType, $index) = $options;

        $resource = $resources[$resourceType][$index];
        $resourceId = $resource->id();
        $resource = $this->api->read($resourceType, $resourceId)->getContent();
        $this->assertNotEmpty($resource);

        $this->performProcessForFile($filepath);

        $resource = $this->api->search($resourceType, ['id' => $resourceId])->getContent();
        $this->assertNotEmpty($resource);

        $resource = reset($resource);
        $expectedFile = $filebase . '.' . $resourceType . '-' . ($index) . '.' . 'api.json';
        if (!file_exists($expectedFile)) {
            return;
        }
        $expected = file_get_contents($expectedFile);
        $expected = $this->cleanApiResult(json_decode($expected, true));
        $resource = $this->cleanApiResult($resource->getJsonLd());
        $this->assertNotEmpty($resource);
        $this->assertEquals($expected, $resource);
    }

    public function csvFileDeleteProvider()
    {
        return [
            ['test_delete_items.csv', ['items', 2]],
            ['test_delete_media.csv', ['media', 4]],
        ];
    }

    /**
     * This test depends on other ones only to avoid check on removed resources.
     *
     * @dataProvider csvFileDeleteProvider
     * @depends testPerformCreateOne
     * @depends testPerformUpdate
     */
    public function testPerformDelete($filepath, $options, $resources)
    {
        $filepath = $this->basepath . $filepath;
        $filebase = substr($filepath, 0, -4);
        list($resourceType, $index) = $options;

        $resource = $resources[$resourceType][$index];
        $resourceId = $resource->id();
        $resource = $this->api->read($resourceType, $resourceId)->getContent();
        $this->assertNotEmpty($resource);

        $this->performProcessForFile($filepath);

        $resource = $this->api->search($resourceType, ['id' => $resourceId])->getContent();
        $this->assertEmpty($resource);
    }

    protected function performProcessForFile($filepath)
    {
        copy($filepath, $this->tempfile);

        $filebase = substr($filepath, 0, -4);
        $args = json_decode(file_get_contents($filebase . '.args.json'), true);
        $args['csvpath'] = $this->tempfile;

        $job = new Job;
        $job->setStatus(Job::STATUS_STARTING);
        $job->setClass(Import::class);
        $job->setArgs($args);
        $job->setOwner($this->auth->getIdentity());
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $import = new Import($job, $this->getServiceLocator());
        $import->perform();
    }

    protected function cleanApiResult(array $resource)
    {
        // Make the representation a pure array.
        $resource = json_decode(json_encode($resource), true);

        unset($resource['@context']);
        unset($resource['@type']);
        unset($resource['@id']);
        unset($resource['o:id']);
        unset($resource['o:created']);
        unset($resource['o:modified']);
        unset($resource['o:owner']['@id']);
        if (isset($resource['o:media'])) {
            foreach ($resource['o:media'] as &$media) {
                unset($media['@id']);
            }
        }
        if (isset($resource['o:item'])) {
            unset($resource['o:item']['@id']);
            unset($resource['o:filename']);
            unset($resource['o:original_url']);
            unset($resource['o:thumbnail_urls']);
        }
        return $resource;
    }
}
