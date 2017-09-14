<?php
namespace CSVImport;

return [
    'controllers' => [
        'factories' => [
            'CSVImport\Controller\Index' => 'CSVImport\Service\Controller\IndexControllerFactory',
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'automapHeadersToMetadata' => Service\ControllerPlugin\AutomapHeadersToMetadataFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'csvimport_entities' => 'CSVImport\Api\Adapter\EntityAdapter',
            'csvimport_imports' => 'CSVImport\Api\Adapter\ImportAdapter',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/CSVImport/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/CSVImport/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'csvPropertySelector' => 'CSVImport\View\Helper\PropertySelector',
        ],
        'factories' => [
            'mediaSidebar' => 'CSVImport\Service\ViewHelper\MediaSidebarFactory',
            'itemSidebar' => 'CSVImport\Service\ViewHelper\ItemSidebarFactory',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/CSVImport/src/Entity',
        ],
    ],
    'form_elements' => [
        'factories' => [
            'CSVImport\Form\ImportForm' => 'CSVImport\Service\Form\ImportFormFactory',
            'CSVImport\Form\MappingForm' => 'CSVImport\Service\Form\MappingFormFactory',
        ],
    ],
    'csv_import_mappings' => [
        'items' => [
            '\CSVImport\Mapping\PropertyMapping',
            '\CSVImport\Mapping\MediaMapping',
            '\CSVImport\Mapping\ItemMapping',
        ],
        'users' => [
            '\CSVImport\Mapping\UserMapping',
        ],
    ],
    'csv_import_media_ingester_adapter' => [
        'url' => 'CSVImport\MediaIngesterAdapter\UrlMediaIngesterAdapter',
        'html' => 'CSVImport\MediaIngesterAdapter\HtmlMediaIngesterAdapter',
        'iiif' => null,
        'oembed' => null,
        'youtube' => null,
    ],
    'csv_import' => [
        'automapping' => [
        ],
        'user_settings' => [
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
                'media url' => 'media {url}',
                'html' => 'media {html}',
                'iiif' => 'media {iiif}',
                'iiif image' => 'media {iiif}',
                'oembed' => 'media {oembed}',
                'youtube' => 'media {youtube}',
                'user' => 'user_name',
                'username' => 'user_name',
                'user name' => 'user_name',
                'email' => 'user_email',
                'user email' => 'user_email',
                'role' => 'user_role',
                'user role' => 'user_role',
                // From module File Sideload, in order to set them by default.
                'media' => 'media {sideload}',
                'file' => 'media {sideload}',
                'files' => 'media {sideload}',
                'upload' => 'media {sideload}',
                'sideload' => 'media {sideload}',
                'file sideload' => 'media {sideload}',
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
    'js_translate_strings' => [
        'Remove mapping', // @translate
        'Undo remove mapping', // @translate
        'Select an item type at the left before choosing a resource class.', // @translate
        'Select an element at the left before choosing a property.', // @translate
        'Please enter a valid language tag.', // @translate
    ],
];
