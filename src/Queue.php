<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use DateInterval;
use DateTimeInterface;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Illuminate\Contracts\Queue\Job as BaseJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as BaseQueue;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use InvalidArgumentException;
use TradeCoverExchange\GoogleCloudTaskLaravel\Factories\TaskFactory;

class Queue extends BaseQueue implements QueueContract
{
    use InteractsWithTime;

    /**
     * @var string
     */
    protected string $location;
    /**
     * @var string
     */
    protected string $defaultQueue;
    /**
     * @var Dispatcher
     */
    protected Dispatcher $client;
    /**
     * @var TaskFactory
     */
    private TaskFactory $factory;

    public function __construct(Dispatcher $client, TaskFactory $factory, string $defaultQueue)
    {
        $this->client = $client;
        $this->factory = $factory;
        $this->defaultQueue = $defaultQueue;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     *
     * @return int
     */
    public function size($queue = null): int
    {
        return $this->client->size($this->getQueue($queue));
    }

    /**
     * Push a new job onto the queue.
     *
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->pushRaw($this->createPayload($job, $this->getQueue($queue), $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $id = json_decode($payload, true)['id'] ?? null;

        if (is_null($id)) {
            throw new \InvalidArgumentException('Argument $payload must contain an `id` attribute.');
        }

        $this->pushGoogleTask($this->getQueue($queue), $payload, $id);

        return $id;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @param object|string $job
     * @param mixed $data
     * @param string|null $queue
     *
     * @return string|null
     */
    public function later($delay, $job, $data = '', $queue = null): string|null
    {
        $this->checkDelayValueIsValid($delay);
        $scheduledAt = $this->availableAt($delay);
        $payload = $this->createPayload($job, $this->getQueue($queue));

        $id = json_decode($payload, true)['id'] ?? null;

        if (is_null($id)) {
            throw new \InvalidArgumentException('Argument $payload must contain an `id` attribute.');
        }

        $this->pushGoogleTask($this->getQueue($queue), $payload, $id, $scheduledAt);

        return $id;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return BaseJob|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function pop($queue = null): BaseJob|null
    {
        $task = $this->factory->make($this->getConnectionName());

        return new Job(
            $this->getConnectionName(),
            $this->container,
            $this,
            $queue ?? $this->defaultQueue,
            $task
        );
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param object|string $job
     * @param string $queue
     * @param mixed $data
     *
     * @return array
     */
    protected function createPayloadArray($job, $queue, $data = ''): array
    {
        $payload = parent::createPayloadArray($job, $queue, $data);

        return [
            ...$payload,
            'id' => $this->getRandomId(),
            'attempts' => $payload['attempts'] ?? 0,
        ];
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId(): string
    {
        return Str::random(32);
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return string
     */
    public function getQueue(string|null $queue): string
    {
        return $queue ?? $this->defaultQueue;
    }

    public function client(): CloudTasksClient
    {
        return $this->client->client();
    }

    /**
     * @param string $queue
     * @param string $payload
     * @param string $name
     * @param int|null $scheduledAt
     */
    protected function pushGoogleTask(string $queue, string $payload, string $name, int|null $scheduledAt = null): void
    {
        $this->client->dispatch($name, $this->getConnectionName(), $payload, $scheduledAt, $queue);
    }

    public function release(Job $runningJob, mixed $job, int $delay = 0): void
    {
        $callbacks = static::$createPayloadCallbacks;

        static::$createPayloadCallbacks[] = function ($connection, $queue, $payload) use ($runningJob) {
            $payload['attempts'] = $payload['attempts'] ?? $runningJob->attempts();
            $payload['id'] = $payload['id'] ?? $this->getRandomId();

            return $payload;
        };

        if ($delay !== 0) {
            $this->later(delay: $delay, job: $job, queue: $runningJob->getQueue());
        } else {
            $this->push(job: $job, queue: $runningJob->getQueue());
        }

        static::$createPayloadCallbacks = $callbacks;
    }

    /**
     * @param DateInterval|DateTimeInterface|int $delay
     */
    protected function checkDelayValueIsValid(mixed $delay): void
    {
        if (
            $delay !== null &&
            ! $delay instanceof DateTimeInterface &&
            ! $delay instanceof DateInterval &&
            ! (is_int($delay) && $delay >= 0)
        ) {
            $type = gettype($delay);
            if (! is_int($delay)) {
                $type = get_class($delay);
            }

            throw new InvalidArgumentException(
                sprintf(
                    'method argument $delay can only be of type \DateTimeInterface, \DateInterval,
                    positive integer or null. Received %s',
                    $type
                )
            );
        }
    }
}
