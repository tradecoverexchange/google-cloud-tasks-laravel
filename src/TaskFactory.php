<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\AppEngineConnector;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\CloudTasksConnector;

class TaskFactory
{
    use ConnectionRetrieval;

    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $connection
     * @return CloudTask
     * @throws BindingResolutionException
     */
    public function make(string $connection) : CloudTask
    {
        $config = $this->getConfig($connection);

        if ($config['driver'] === AppEngineConnector::DRIVER) {
            return new CloudTask\AppEngineTask($this->container->make(Request::class));
        } elseif ($config['driver'] === CloudTasksConnector::DRIVER) {
            return new CloudTask\HttpCloudTask($this->container->make(Request::class));
        }

        throw new \RuntimeException();
    }
}
