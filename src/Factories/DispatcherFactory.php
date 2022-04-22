<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Factories;

use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatchers\AppEngineDispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatchers\HttpDispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;
use TradeCoverExchange\GoogleCloudTaskLaravel\RequestGenerator;

class DispatcherFactory
{
    public function __construct(protected CloudTaskClientFactory $factory, protected RequestGenerator $generator)
    {
    }

    public function makeAppEngineDispatcher(
        string $projectId,
        string $location,
        array $clientOptions
    ): Dispatcher {
        return new AppEngineDispatcher(
            $this->factory->make($clientOptions),
            $this->generator,
            $projectId,
            $location
        );
    }

    public function makeCloudTasksDispatcher(
        string $projectId,
        string $location,
        array $clientOptions,
        HttpRequestAuthenticator $authenticator
    ): Dispatcher {
        return new HttpDispatcher(
            $this->factory->make($clientOptions),
            $this->generator,
            $projectId,
            $location,
            $authenticator
        );
    }
}
