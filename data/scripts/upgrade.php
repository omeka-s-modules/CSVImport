<?php
namespace CSVImport;

$services = $serviceLocator;
/** @var \Doctrine\DBAL\Connection $connection */
$connection = $serviceLocator->get('Omeka\Connection');
/** @var \Omeka\Settings\Settings $settings */
$settings = $serviceLocator->get('Omeka\Settings');
$config = require dirname(dirname(__DIR__)) . '/config/module.config.php';

if (version_compare($oldVersion, '1.1.1-rc.1', '<')) {
    $sql = <<<'SQL'
ALTER TABLE csvimport_import ADD stats LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)';
UPDATE csvimport_import SET stats = CONCAT('{"processed":{"', resource_type, '":', added_count, '}}');
ALTER TABLE csvimport_import DROP added_count;
SQL;
    $sqls = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($sqls as $sql) {
        $connection->exec($sql);
    }
}

if (version_compare($oldVersion, '1.1.3', '<')) {
    $settings->delete('csv_import_rows_by_loop');
    /*
    $options = [
        'csv_import_delimiter' => 'csvimport_delimiter',
        'csv_import_enclosure' => 'csvimport_enclosure',
        'csv_import_multivalue_separator' => 'csvimport_multivalue_separator',
        'csv_import_multivalue_by_default' => 'csvimport_multivalue_by_default',
        'csv_import_rows_by_batch' => 'csvimport_rows_by_batch',
        'csv_import_global_language' => 'csvimport_global_language',
        'csv_import_identifier_property' => 'csvimport_identifier_property',
        'csv_import_automap_check_names_alone' => 'csvimport_automap_check_names_alone',
        'csv_import_automap_check_user_list' => 'csvimport_automap_check_user_list',
        'csv_import_automap_user_list' => 'csvimport_automap_user_list',
    ];
    */
    $sql = <<<'SQL'
UPDATE `user_setting`
SET `id` = CONCAT('csvimport_', SUBSTRING(id, 12))
WHERE `id` LIKE 'csv_import_%';
SQL;
    $sqls = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($sqls as $sql) {
        $connection->exec($sql);
    }
}
