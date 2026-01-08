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
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'CSVImport\Form\MappingModelEditForm' => Form\MappingModelEditForm::class,
            'CSVImport\Form\MappingModelSelectForm' => Form\MappingModelSelectForm::class,
        ],
        'factories' => [
            'CSVImport\Form\ImportForm' => Service\Form\ImportFormFactory::class,
            'CSVImport\Form\MappingModelForm' => Service\Form\MappingModelFormFactory::class,
            'CSVImport\Form\Element\MappingModelSelect' => Service\Form\Element\MappingModelSelectFactory::class,
            'CSVImport\Form\MappingModelSaveForm' => Service\Form\MappingModelSaveFormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'CSVImport\Controller\Index' => Service\Controller\IndexControllerFactory::class,
            'CSVImport\Controller\Admin\MappingModel' => Service\Controller\Admin\MappingModelControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'automapHeadersToMetadata' => Service\ControllerPlugin\AutomapHeadersToMetadataFactory::class,
            'findResourcesFromIdentifiers' => Service\ControllerPlugin\FindResourcesFromIdentifiersFactory::class,
            'loadMappingModel' => Service\ControllerPlugin\LoadMappingModelFactory::class,
            'saveMappingModel' => Service\ControllerPlugin\SaveMappingModelFactory::class,
        ],
        'aliases' => [
            'findResourceFromIdentifier' => 'findResourcesFromIdentifiers',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'csvimport_entities' => Api\Adapter\EntityAdapter::class,
            'csvimport_imports' => Api\Adapter\ImportAdapter::class,
            'csvimport_mapping_models' => Api\Adapter\MappingModelAdapter::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'CSVImport\Config' => Service\ConfigFactory::class,
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
                            'mapping-model' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/mapping-model[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller\Admin',
                                        'controller' => 'MappingModel',
                                        'action' => 'browse',
                                    ],
                                ],
                            ],
                            'mapping-model-id' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/mapping-model/:id[/:action]',
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'id' => '\d+',
                                    ],
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller\Admin',
                                        'controller' => 'MappingModel',
                                        'action' => 'show',
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
                    [
                        'label' => 'Mapping Models', // @translate
                        'route' => 'admin/csvimport/mapping-model',
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
        'Remove mapping model', // @translate
    ],
    'csv_import' => [
        'sources' => [
            'application/vnd.oasis.opendocument.spreadsheet' => Source\OpenDocumentSpreadsheet::class,
            'text/csv' => Source\CsvFile::class,
            'application/csv' => Source\CsvFile::class,
            'text/tab-separated-values' => Source\TsvFile::class,
        ],
        'mappings' => [
            'items' => [
                Mapping\PropertyMapping::class,
                Mapping\ItemMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'item_sets' => [
                Mapping\PropertyMapping::class,
                Mapping\ItemSetMapping::class,
            ],
            'media' => [
                Mapping\PropertyMapping::class,
                Mapping\MediaMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'resources' => [
                Mapping\PropertyMapping::class,
                Mapping\ResourceMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'users' => [
                Mapping\UserMapping::class,
            ],
        ],
        'data_types' => [
            'literal' => [
                'label' => 'Text', // @translate
                'adapter' => 'literal',
            ],
            'uri' => [
                'label' => 'URI', // @translate
                'adapter' => 'uri',
            ],
            'resource' => [
                'label' => 'Omeka resource', // @translate
                'adapter' => 'resource',
            ],
        ],
        'media_ingester_adapter' => [
            'url' => MediaIngesterAdapter\UrlMediaIngesterAdapter::class,
            'html' => MediaIngesterAdapter\HtmlMediaIngesterAdapter::class,
            'iiif' => null,
            'iiif_presentation' => null,
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
