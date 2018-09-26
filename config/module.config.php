<?php
namespace CSVImport;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'csvPropertySelector' => View\Helper\PropertySelector::class,
        ],
        'factories' => [
            'mediaSourceSidebar' => Service\ViewHelper\MediaSourceSidebarFactory::class,
            'resourceSidebar' => Service\ViewHelper\ResourceSidebarFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'CSVImport\Form\ImportForm' => Service\Form\ImportFormFactory::class,
            'CSVImport\Form\MappingForm' => Service\Form\MappingFormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'CSVImport\Controller\Index' => Service\Controller\IndexControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'automapHeadersToMetadata' => Service\ControllerPlugin\AutomapHeadersToMetadataFactory::class,
            'findResourcesFromIdentifiers' => Service\ControllerPlugin\FindResourcesFromIdentifiersFactory::class,
        ],
        'aliases' => [
            'findResourceFromIdentifier' => 'findResourcesFromIdentifiers',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'csvimport_entities' => Api\Adapter\EntityAdapter::class,
            'csvimport_imports' => Api\Adapter\ImportAdapter::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'csvimport' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/csvimport',
                            'defaults' => [
                                '__NAMESPACE__' => 'CSVImport\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'past-imports' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/past-imports',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller' => 'Index',
                                        'action' => 'past-imports',
                                    ],
                                ],
                            ],
                            'map' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/map',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller' => 'Index',
                                        'action' => 'map',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'CSV Import',
                'route' => 'admin/csvimport',
                'resource' => 'CSVImport\Controller\Index',
                'pages' => [
                    [
                        'label' => 'Import', // @translate
                        'route' => 'admin/csvimport',
                        'resource' => 'CSVImport\Controller\Index',
                    ],
                    [
                        'label' => 'Import', // @translate
                        'route' => 'admin/csvimport/map',
                        'resource' => 'CSVImport\Controller\Index',
                        'visible' => false,
                    ],
                    [
                        'label' => 'Past Imports', // @translate
                        'route' => 'admin/csvimport/past-imports',
                        'controller' => 'Index',
                        'action' => 'past-imports',
                        'resource' => 'CSVImport\Controller\Index',
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Remove mapping', // @translate
    ],
    'csv_import' => [
        'sources' => [
            'application/vnd.oasis.opendocument.spreadsheet' => Source\OpenDocumentSpreadsheet::class,
            'text/csv' => Source\CsvFile::class,
            'text/tab-separated-values' => Source\TsvFile::class,
        ],
        'mappings' => [
            'items' => [
                Mapping\ItemMapping::class,
                Mapping\PropertyMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'item_sets' => [
                Mapping\ItemSetMapping::class,
                Mapping\PropertyMapping::class,
            ],
            'media' => [
                Mapping\MediaMapping::class,
                Mapping\PropertyMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'resources' => [
                Mapping\ResourceMapping::class,
                Mapping\PropertyMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'users' => [
                Mapping\UserMapping::class,
            ],
        ],
        'media_ingester_adapter' => [
            'url' => MediaIngesterAdapter\UrlMediaIngesterAdapter::class,
            'html' => MediaIngesterAdapter\HtmlMediaIngesterAdapter::class,
            'iiif' => null,
            'oembed' => null,
            'youtube' => null,
        ],
        'user_settings' => [
            'csv_import_delimiter' => ',',
            'csv_import_enclosure' => '"',
            'csv_import_multivalue_separator' => ',',
            'csv_import_rows_by_batch' => 20,
            'csv_import_global_language' => '',
            'csv_import_identifier_property' => '',
            'csv_import_automap_check_names_alone' => false,
        ],
    ],
];
