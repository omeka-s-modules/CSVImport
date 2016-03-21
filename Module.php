<?php
namespace CSVImport;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\Mvc\Controller\AbstractController;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = "
        CREATE TABLE csvimport_record (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, item_id INT DEFAULT NULL, item_set_id INT DEFAULT NULL, INDEX IDX_11CF590DBE04EA9 (job_id), UNIQUE INDEX UNIQ_11CF590D126F525E (item_id), UNIQUE INDEX UNIQ_11CF590D960278D7 (item_set_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
        CREATE TABLE csvimport_import (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, undo_job_id INT DEFAULT NULL, added_count INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_17B50881BE04EA9 (job_id), UNIQUE INDEX UNIQ_17B508814C276F75 (undo_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
        ALTER TABLE csvimport_record ADD CONSTRAINT FK_11CF590DBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
        ALTER TABLE csvimport_record ADD CONSTRAINT FK_11CF590D126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;
        ALTER TABLE csvimport_record ADD CONSTRAINT FK_11CF590D960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id);
        ALTER TABLE csvimport_import ADD CONSTRAINT FK_17B50881BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
        ALTER TABLE csvimport_import ADD CONSTRAINT FK_17B508814C276F75 FOREIGN KEY (undo_job_id) REFERENCES job (id);
        ";
        $connection->exec($sql);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("ALTER TABLE csvimport_record DROP FOREIGN KEY FK_11CF590DBE04EA9;");
        $connection->exec("ALTER TABLE csvimport_record DROP FOREIGN KEY FK_11CF590D126F525E;");
        $connection->exec("ALTER TABLE csvimport_record DROP FOREIGN KEY FK_11CF590D960278D7;");
        $connection->exec("ALTER TABLE csvimport_import DROP FOREIGN KEY FK_17B50881BE04EA9;");
        $connection->exec("ALTER TABLE csvimport_import DROP FOREIGN KEY FK_17B508814C276F75;");
        $connection->exec("DROP TABLE csvimport_record;");
        $connection->exec("DROP TABLE csvimport_import;");
    }
}
