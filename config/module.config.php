<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'CSVImport\Controller\Index' => 'CSVImport\Controller\IndexController',
        ),
    ),
    'api_adapters' => array(
        'invokables' => array(
            'csvimport_entities' => 'CSVImport\Api\Adapter\EntityAdapter',
            'csvimport_imports' => 'CSVImport\Api\Adapter\ImportAdapter'
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/CSVImport/view',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'csvPropertySelector' => 'CSVImport\View\Helper\PropertySelector',
        ),
        'factories'  => [
            'mediaSidebar'    => 'CSVImport\Service\ViewHelper\MediaSidebarFactory',
            'itemSidebar'     => 'CSVImport\Service\ViewHelper\ItemSidebarFactory',
        ],
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/CSVImport/src/Entity',
        ),
    ),
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
    'router' => array(
        'routes' => array(
            'admin' => array(
                'child_routes' => array(
                    'csvimport' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/csvimport',
                            'defaults' => array(
                                '__NAMESPACE__' => 'CSVImport\Controller',
                                'controller'    => 'Index',
                                'action'        => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'past-imports' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/past-imports',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'past-imports',
                                    ),
                                )
                            ),
                            'map' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/map',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'map',
                                    ),
                                )
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'navigation' => array(
        'AdminGlobal' => array(
            array(
                'label'      => 'CSV Importer',
                'route'      => 'admin/csvimport',
                'resource'   => 'CSVImport\Controller\Index',
                'pages'      => array(
                    array(
                        'label'      => 'Import',
                        'route'      => 'admin/csvimport',
                        'resource'   => 'CSVImport\Controller\Index',
                    ),
                    array(
                        'label'      => 'Import',
                        'route'      => 'admin/csvimport/map',
                        'resource'   => 'CSVImport\Controller\Index',
                        'visible'    => false,
                    ),
                    array(
                        'label'      => 'Past Imports',
                        'route'      => 'admin/csvimport/past-imports',
                        'controller' => 'Index',
                        'action'     => 'past-imports',
                        'resource'   => 'CSVImport\Controller\Index',
                    ),
                ),
            ),
        ),
    )
);
