<?php
namespace CSVImport\Mapping;

abstract class AbstractMapping implements MappingInterface
{
    protected $args;

    protected $api;

    protected $logger;

    protected $serviceLocator;

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
