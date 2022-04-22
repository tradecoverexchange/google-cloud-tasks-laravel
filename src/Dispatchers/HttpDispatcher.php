<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Dispatchers;

use Google\ApiCore\ApiException;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient as Client;
use Google\Cloud\Tasks\V2beta3\Task;
use Google\Protobuf\FieldMask;
use Google\Protobuf\Timestamp;
use TradeCoverExchange\GoogleCloudTaskLaravel\Dispatcher;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;
use TradeCoverExchange\GoogleCloudTaskLaravel\RequestGenerator;

class HttpDispatcher implements Dispatcher
{
    use BreakoutApiErrors;

    public function __construct(
        protected Client $client,
        protected RequestGenerator $generator,
        protected string $projectId,
        protected string $location,
        protected ?HttpRequestAuthenticator $authenticator = null
    ) {
    }

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function dispatch(
        string $name,
        string $connection,
        string $payload,
        int|null $scheduledAt = null,
        string $queue = 'default'
    ): void {
        $httpRequest = $this->generator->forHttpHandler($payload, $connection, $queue);

        if ($this->authenticator) {
            $httpRequest = $this->authenticator->addAuthentication($httpRequest);
        }

        $task = new Task();
        $task->setName(Client::taskName($this->projectId, $this->location, $queue, $name));
        $task->setHttpRequest($httpRequest);
        if ($scheduledAt !== null) {
            $task->setScheduleTime((new Timestamp())->setSeconds($scheduledAt));
        }

        $queueName = Client::queueName($this->projectId, $this->location, $queue);

        try {
            $this->client->createTask($queueName, $task);
        } catch (ApiException $exception) {
            throw $this->breakoutException($exception, $connection, $queueName) ?? $exception;
        }
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function size($queue = null): int
    {
        $queueName = Client::queueName($this->projectId, $this->location, $queue);
        $cloudQueue = $this->client->getQueue($queueName, [
            'readMask' => new FieldMask(['paths' => ['stats.tasksCount']])
        ]);
        return (int) $cloudQueue->getStats()->getTasksCount();
    }
}
