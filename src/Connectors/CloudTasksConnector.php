<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Connectors;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use TradeCoverExchange\GoogleCloudTaskLaravel\Authenticator\OidcAuthenticator;
use TradeCoverExchange\GoogleCloudTaskLaravel\DispatcherFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Queue;
use TradeCoverExchange\GoogleCloudTaskLaravel\RequestGenerator;
use TradeCoverExchange\GoogleCloudTaskLaravel\TaskFactory;

class CloudTasksConnector implements ConnectorInterface
{
    public const DRIVER = 'google_http_cloud_tasks';

    /**
     * @var DispatcherFactory
     */
    private $dispatcherFactory;
    /**
     * @var RequestGenerator
     */
    private $generator;
    /**
     * @var TaskFactory
     */
    private $taskFactory;

    public function __construct(
        DispatcherFactory $dispatcherFactory,
        RequestGenerator $generator,
        TaskFactory $taskFactory
    ) {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->generator = $generator;
        $this->taskFactory = $taskFactory;
    }

    public function connect(array $config) : QueueContract
    {
        $authenticator = new OidcAuthenticator(
            $config['authentication']['service_account']
        );

        return new Queue(
            $this->dispatcherFactory->makeCloudTasksDispatcher(
                $config['project_id'] ?? '',
                $config['location'] ?? '',
                $config['options'] ?? [],
                $authenticator
            ),
            $this->taskFactory,
            $config['queue'] ?? 'default'
        );
    }
}
