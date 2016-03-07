<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'CSVImport\Controller\Index' => 'CSVImport\Controller\IndexController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/CSVImport/view',
        ),
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/CSVImport/src/Entity',
        ),
    ),
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
                            'map-elements' => array(
                                'type'    => 'Literal',
                                'options' => array(
                                    'route' => '/map-elements',
                                    'defaults' => array(
                                        '__NAMESPACE__' => 'CSVImport\Controller',
                                        'controller'    => 'Index',
                                        'action'        => 'map-elements',
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
        'admin' => array(
            array(
                'label'      => 'Csv Importer',
                'route'      => 'admin/csvimport',
                'resource'   => 'CSVImport\Controller\Index',
                'pages'      => array(
                    array(
                        'label'      => 'Import',
                        'route'      => 'admin/csvimport',
                        'resource'   => 'CSVImport\Controller\Index',
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