<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient as Client;
use Google\Cloud\Tasks\V2beta3\Task;
use Google\Protobuf\Timestamp;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\RequestGenerator;

class AppEngineDispatcher implements Dispatcher
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $projectId;
    /**
     * @var string
     */
    protected $locationId;
    /**
     * @var RequestGenerator
     */
    private $generator;

    public function __construct(Client $client, RequestGenerator $generator, string $projectId, string $locationId)
    {
        $this->client = $client;
        $this->generator = $generator;
        $this->projectId = $projectId;
        $this->locationId = $locationId;
    }

    /**
     * @param string $name
     * @param string $connection
     * @param string $payload
     * @param int|null $scheduledAt
     * @param string $queue
     * @throws \Google\ApiCore\ApiException
     */
    public function dispatch(
        string $name,
        string $connection,
        string $payload,
        ?int $scheduledAt = null,
        string $queue = 'default'
    ): void {
        $httpRequest = $this->generator->forAppEngine($payload, $connection);

        // Create a Cloud Task object.
        $task = new Task();
        $task->setName(CloudTasksClient::taskName($this->projectId, $this->locationId, $queue, $name));
        $task->setAppEngineHttpRequest($httpRequest);
        if ($scheduledAt !== null) {
            $task->setScheduleTime((new Timestamp())->setSeconds($scheduledAt));
        }

        $queueName = CloudTasksClient::queueName($this->projectId, $this->locationId, $queue);

        $this->client->createTask($queueName, $task);
    }
}
