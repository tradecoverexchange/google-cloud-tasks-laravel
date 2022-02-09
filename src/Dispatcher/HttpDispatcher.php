<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient as Client;
use Google\Cloud\Tasks\V2beta3\Task;
use Google\Protobuf\Timestamp;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;
use TradeCoverExchange\GoogleCloudTaskLaravel\RequestGenerator;

class HttpDispatcher implements Dispatcher
{
    /**
     * @var Client
     */
    private $client;
    /**
     * @var string
     */
    private $projectId;
    /**
     * @var string
     */
    private $locationId;
    /**
     * @var RequestGenerator
     */
    private $generator;
    /**
     * @var HttpRequestAuthenticator|null
     */
    private $authenticator;

    public function __construct(
        Client $client,
        RequestGenerator $generator,
        string $projectId,
        string $locationId,
        ?HttpRequestAuthenticator $authenticator = null
    ) {
        $this->client = $client;
        $this->generator = $generator;
        $this->projectId = $projectId;
        $this->locationId = $locationId;
        $this->authenticator = $authenticator;
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
        $httpRequest = $this->generator->forHttpHandler($payload, $connection);

        if ($this->authenticator) {
            $httpRequest = $this->authenticator->addAuthentication($httpRequest);
        }

        // Create a Cloud Task object.
        $task = new Task();
        $task->setName(CloudTasksClient::taskName($this->projectId, $this->locationId, $queue, $name));
        $task->setHttpRequest($httpRequest);
        if ($scheduledAt !== null) {
            $task->setScheduleTime((new Timestamp())->setSeconds($scheduledAt));
        }

        $queueName = CloudTasksClient::queueName($this->projectId, $this->locationId, $queue);

        $this->client->createTask($queueName, $task);
    }
}
