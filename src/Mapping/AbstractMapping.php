<?php
namespace CSVImport\Mapping;

use Omeka\Api\Manager;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceManager;

abstract class AbstractMapping implements MappingInterface
{
    /**
     * @var array
     */
    protected $args;

    /**
     * @var Manager
     */
    protected $api;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ServiceManager
     */
    protected $serviceLocator;

    /**
     * @var bool
     */
    protected $hasErr = false;

    public function __construct($args, $serviceLocator)
    {
        $this->args = $args;
        $this->logger = $serviceLocator->get('Omeka\Logger');
        $this->api = $serviceLocator->get('Omeka\ApiManager');
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setHasErr($hasErr)
    {
        $this->hasErr = $hasErr;
    }

    public function getHasErr()
    {
        return $this->hasErr;
    }
}
