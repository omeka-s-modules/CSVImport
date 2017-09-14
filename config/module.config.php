<?php
namespace CSVImport;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
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
            'findResourcesFromIdentifiers' => Service\ControllerPlugin\FindResourcesFromIdentifiersFactory::class,
        ],
        'factories' => [
            'automapHeadersToMetadata' => Service\ControllerPlugin\AutomapHeadersToMetadataFactory::class,
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
    'csv_import' => [
        'mappings' => [
            'item_sets' => [
                Mapping\ItemSetMapping::class,
                Mapping\PropertyMapping::class,
            ],
            'items' => [
                Mapping\ItemMapping::class,
                Mapping\PropertyMapping::class,
                Mapping\MediaSourceMapping::class,
            ],
            'media' => [
                Mapping\MediaMapping::class,
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
        'automapping' => [
        ],
        'user_settings' => [
            'csv_import_delimiter' => ',',
            'csv_import_enclosure' => '"',
            'csv_import_multivalue_separator' => ',',
            'csv_import_multivalue_by_default' => false,
            'csv_import_global_language' => '',
            'csv_import_identifier_property' => '',
            'csv_import_automap_check_names_alone' => false,
            'csv_import_automap_check_user_list' => false,
            'csv_import_automap_user_list' => [
                'owner' => 'owner_email',
                'owner email' => 'owner_email',
                'resource template' => 'resource_template',
                'item type' => 'resource_class',
                'resource class' => 'resource_class',
                'item sets' => 'item_sets',
                'item set' => 'item_sets',
                'collections' => 'item_sets',
                'collection' => 'item_sets',
                'media url' => 'media_url',
                'html' => 'media_html',
                'iiif' => 'media_iiif',
                'iiif image' => 'media_iiif',
                'oembed' => 'media_oEmbed',
                'youtube' => 'media_youtube',
                'user' => 'user_name',
                'username' => 'user_name',
                'user name' => 'user_name',
                'email' => 'user_email',
                'user email' => 'user_email',
                'role' => 'user_role',
                'user role' => 'user_role',
                // From module File Sideload, in order to set them by default.
                'media' => 'media_file_sideload',
                'file' => 'media_file_sideload',
                'files' => 'media_file_sideload',
                'upload' => 'media_file_sideload',
                'sideload' => 'media_file_sideload',
                'file sideload' => 'media_file_sideload',
                // From module Mapping.
                'latitude' => 'mapping_latitude',
                'longitude' => 'mapping_longitude',
                'latitude / longitude' => 'mapping_latitude_longitude',
                'latitude/longitude' => 'mapping_latitude_longitude',
                'default latitude' => 'mapping_default_latitude',
                'default longitude' => 'mapping_default_longitude',
                'default zoom' => 'mapping_default_zoom',
            ],
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
                        'label'      => 'Import', // @translate
                        'route'      => 'admin/csvimport',
                        'resource'   => 'CSVImport\Controller\Index',
                    ],
                    [
                        'label'      => 'Import', // @translate
                        'route'      => 'admin/csvimport/map',
                        'resource'   => 'CSVImport\Controller\Index',
                        'visible'    => false,
                    ],
                    [
                        'label'      => 'Past Imports', // @translate
                        'route'      => 'admin/csvimport/past-imports',
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
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Remove mapping', // @translate
        'Undo remove mapping', // @translate
        'Select an item type at the left before choosing a resource class.', // @translate
        'Select an element at the left before choosing a property.', // @translate
        'Please enter a valid language tag.', // @translate
        'Set multivalue separator for all columns', // @translate
        'Unset multivalue separator for all columns', // @translate
        'Advanced settings', // @translate
    ],
];
