<?php
namespace CSVImport;

use Omeka\Module\AbstractModule;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function init(ModuleManager $moduleManager)
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
CREATE TABLE csvimport_import (
  id INT AUTO_INCREMENT NOT NULL,
  job_id INT NOT NULL,
  undo_job_id INT DEFAULT NULL,
  comment VARCHAR(255) DEFAULT NULL,
  resource_type VARCHAR(255) NOT NULL,
  has_err TINYINT(1) NOT NULL,
  stats LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)',
  UNIQUE INDEX UNIQ_17B50881BE04EA9 (job_id),
  UNIQUE INDEX UNIQ_17B508814C276F75 (undo_job_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE csvimport_entity (
  id INT AUTO_INCREMENT NOT NULL,
  job_id INT NOT NULL,
  entity_id INT NOT NULL,
  resource_type VARCHAR(255) NOT NULL,
  INDEX IDX_84D382F4BE04EA9 (job_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE csvimport_import ADD CONSTRAINT FK_17B50881BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
ALTER TABLE csvimport_import ADD CONSTRAINT FK_17B508814C276F75 FOREIGN KEY (undo_job_id) REFERENCES job (id);
ALTER TABLE csvimport_entity ADD CONSTRAINT FK_84D382F4BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
SQL;
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
ALTER TABLE csvimport_entity DROP FOREIGN KEY FK_84D382F4BE04EA9;
ALTER TABLE csvimport_import DROP FOREIGN KEY FK_17B508814C276F75;
ALTER TABLE csvimport_import DROP FOREIGN KEY FK_17B50881BE04EA9;
DROP TABLE IF EXISTS csvimport_entity;
DROP TABLE IF EXISTS csvimport_import;
SQL;
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }
        // User settings are not removed here: they belong to the user.
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        if (version_compare($oldVersion, '1.1.1-rc.1', '<')) {
            $connection = $serviceLocator->get('Omeka\Connection');
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
    }
}
