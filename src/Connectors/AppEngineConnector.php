<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Connectors;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use TradeCoverExchange\GoogleCloudTaskLaravel\DispatcherFactory;
use TradeCoverExchange\GoogleCloudTaskLaravel\Queue;
use TradeCoverExchange\GoogleCloudTaskLaravel\TaskFactory;

class AppEngineConnector implements ConnectorInterface
{
    public const DRIVER = 'google_app_engine_cloud_tasks';

    /**
     * @var DispatcherFactory
     */
    private $dispatcherFactory;
    /**
     * @var TaskFactory
     */
    private $taskFactory;

    public function __construct(DispatcherFactory $dispatcherFactory, TaskFactory $taskFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->taskFactory = $taskFactory;
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
