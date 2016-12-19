<?php

return [
    'controllers' => [
        'factories' => [
            'CSVImport\Controller\Index' => 'CSVImport\Service\Controller\IndexControllerFactory',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'csvimport_entities' => 'CSVImport\Api\Adapter\EntityAdapter',
            'csvimport_imports' => 'CSVImport\Api\Adapter\ImportAdapter'
        ],
    ],
    'view_manager' => [
        'template_path_stack'      => [
            OMEKA_PATH . '/modules/CSVImport/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'csvPropertySelector' => 'CSVImport\View\Helper\PropertySelector',
        ],
        'factories'  => [
            'mediaSidebar'    => 'CSVImport\Service\ViewHelper\MediaSidebarFactory',
            'itemSidebar'     => 'CSVImport\Service\ViewHelper\ItemSidebarFactory',
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
            '\CSVImport\Mapping\UserMapping'
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'csvimport' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/csvimport',
                            'defaults' => [
                                '__NAMESPACE__' => 'CSVImport\Controller',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'past-imports' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route' => '/past-imports',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'past-imports',
                                    ],
                                ]
                            ],
                            'map' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route' => '/map',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'map',
                                    ],
                                ]
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
                'label'      => 'CSV Import',
                'route'      => 'admin/csvimport',
                'resource'   => 'CSVImport\Controller\Index',
                'pages'      => [
                    [
                        'label'      => 'Import',
                        'route'      => 'admin/csvimport',
                        'resource'   => 'CSVImport\Controller\Index',
                    ],
                    [
                        'label'      => 'Import',
                        'route'      => 'admin/csvimport/map',
                        'resource'   => 'CSVImport\Controller\Index',
                        'visible'    => false,
                    ],
                    [
                        'label'      => 'Past Imports',
                        'route'      => 'admin/csvimport/past-imports',
                        'controller' => 'Index',
                        'action'     => 'past-imports',
                        'resource'   => 'CSVImport\Controller\Index',
                    ],
                ],
            ],
        ],
    ]
];
