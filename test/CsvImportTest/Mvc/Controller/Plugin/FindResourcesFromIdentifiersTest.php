<?php
namespace CSVImportTest\Mvc\Controller\Plugin;

use CSVImport\Mvc\Controller\Plugin\FindResourcesFromIdentifiers;
use OmekaTestHelper\Controller\OmekaControllerTestCase;

class FindResourcesFromIdentifiersTest extends OmekaControllerTestCase
{
    protected $connection;
    protected $api;
    protected $findResourcesFromIdentifier;

    protected $itemSet;
    protected $item;
    protected $media;

    public function setUp()
    {
        parent::setup();

        $services = $this->getServiceLocator();
        $this->connection = $services->get('Omeka\Connection');
        $this->api = $services->get('Omeka\ApiManager');
        $this->findResourcesFromIdentifier = new FindResourcesFromIdentifiers($this->connection, $this->api);

        $this->loginAsAdmin();

        // 10 is property id of dcterms:identifier.
        $this->itemSet = $this->api->create('item_sets', [
            'dcterms:identifier' => [
                0 => [
                    'property_id' => 10,
                    'type' => 'literal',
                    '@value' => 'foo item set',
                ],
            ],
        ])->getContent();

        $this->item = $this->api->create('items', [
            'dcterms:identifier' => [
                0 => [
                    'property_id' => 10,
                    'type' => 'literal',
                    '@value' => 'foo item',
                ],
            ],
        ])->getContent();

        $this->media = $this->api->create('media',
            [
                'dcterms:identifier' => [
                    0 => [
                        'property_id' => 10,
                        'type' => 'literal',
                        '@value' => 'foo media',
                    ],
                ],
                'o:ingester' => 'html',
                'html' => '<p>This <strong>is</strong> <em>html</em>.</p>',
                'o:item' => ['o:id' => $this->item->id()],
        ])->getContent();
    }

    public function tearDown()
    {
        $this->api()->delete('item_sets', $this->itemSet->id());
        $this->api()->delete('items', $this->item->id());
    }

    public function testNoIdentifier()
    {
        $findResourcesFromIdentifier = $this->findResourcesFromIdentifier;
        $item = $this->api->create('items', [])->getContent();

        $identifierProperty = 'internal_id';
        $resourceType = null;

        $identifier = '';
        $resource = $findResourcesFromIdentifier($identifier, $identifierProperty, $resourceType);
        $this->assertNull($resource);

        $identifiers = [];
        $resources = $findResourcesFromIdentifier($identifiers, $identifierProperty, $resourceType);
        $this->assertTrue(is_array($resources));
        $this->assertEmpty($resources);
    }

    public function testItemSetIdentifier()
    {
        $findResourcesFromIdentifier = $this->findResourcesFromIdentifier;
        $resource = $findResourcesFromIdentifier('foo item set', 10, 'item_sets');
        $this->assertEquals($this->itemSet->id(), $resource);

        $resources = $findResourcesFromIdentifier(['foo item set'], 10, 'item_sets');
        $this->assertEquals(1, count($resources));
        $this->assertEquals($this->itemSet->id(), $resources['foo item set']);
    }

    public function testItemIdentifier()
    {
        $findResourcesFromIdentifier = $this->findResourcesFromIdentifier;
        $resource = $findResourcesFromIdentifier('foo item', 10, 'items');
        $this->assertEquals($this->item->id(), $resource);

        $resources = $findResourcesFromIdentifier(['foo item'], 10, 'items');
        $this->assertEquals(1, count($resources));
        $this->assertEquals($this->item->id(), $resources['foo item']);
    }

    public function testMediaIdentifier()
    {
        $findResourcesFromIdentifier = $this->findResourcesFromIdentifier;
        $resource = $findResourcesFromIdentifier('foo media', 10, 'media');
        $this->assertEquals($this->media->id(), $resource);

        $resources = $findResourcesFromIdentifier(['foo media'], 10, 'media');
        $this->assertEquals(1, count($resources));
        $this->assertEquals($this->media->id(), $resources['foo media']);
    }
}
