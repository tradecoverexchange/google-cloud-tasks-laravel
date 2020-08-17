<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher\AppEngineDispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher\HttpDispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\CloudTaskClientFactory;

class DispatcherFactory
{
    /**
     * @var CloudTaskClientFactory
     */
    private $factory;
    /**
     * @var RequestGenerator
     */
    private $generator;

    public function __construct(CloudTaskClientFactory $factory, RequestGenerator $generator)
    {
        $this->factory = $factory;
        $this->generator = $generator;
    }

    public function makeAppEngineDispatcher(
        string $projectId,
        string $location,
        array $clientOptions
    ) :  Dispatcher {
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
    ) : Dispatcher {
        return new HttpDispatcher(
            $this->factory->make($clientOptions),
            $this->generator,
            $projectId,
            $location,
            $authenticator
        );
    }
}
