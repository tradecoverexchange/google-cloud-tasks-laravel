<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Connectors;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\DispatcherFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\TaskFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Queue;

class AppEngineConnector implements ConnectorInterface
{
    public const DRIVER = 'google_app_engine_cloud_tasks';

    public function __construct(protected DispatcherFactory $dispatcherFactory, protected TaskFactory $taskFactory)
    {
    }

    public function connect(array $config): QueueContract
    {
        return new Queue(
            $this->dispatcherFactory->makeAppEngineDispatcher(
                $config['project_id'] ?? '',
                $config['location'] ?? '',
                $config['options'] ?? []
            ),
            $this->taskFactory,
            $config['queue'] ?? 'default'
        );
    }
}
