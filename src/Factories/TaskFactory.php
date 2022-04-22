<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Factories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudTask;
use TradeCoverExchange\GoogleCloudTaskLaravel\ConnectionRetrieval;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\AppEngineConnector;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\CloudTasksConnector;
use TradeCoverExchange\GoogleCloudTaskLaravel\Tasks;

class TaskFactory
{
    use ConnectionRetrieval;

    public function __construct(protected Container $container)
    {
    }

    /**
     * @param string $connection
     * @return CloudTask
     * @throws BindingResolutionException
     */
    public function make(string $connection): CloudTask
    {
        $config = $this->getConfig($connection);

        if ($config['driver'] === AppEngineConnector::DRIVER) {
            return new Tasks\AppEngineTask($this->container->make(Request::class));
        } elseif ($config['driver'] === CloudTasksConnector::DRIVER) {
            return new Tasks\HttpCloudTask($this->container->make(Request::class));
        }

        throw new \RuntimeException();
    }
}
