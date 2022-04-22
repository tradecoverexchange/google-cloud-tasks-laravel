<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Connectors;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use TradeCoverExchange\GoogleCloudTaskLaravel\Authenticators\OidcAuthenticator;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\DispatcherFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\TaskFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Queue;

class CloudTasksConnector implements ConnectorInterface
{
    public const DRIVER = 'google_http_cloud_tasks';

    public function __construct(
        protected DispatcherFactory $dispatcherFactory,
        protected TaskFactory $taskFactory
    ) {
    }

    public function connect(array $config): QueueContract
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
