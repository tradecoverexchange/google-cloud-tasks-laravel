<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\Job as BaseJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as BaseQueue;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Queue extends BaseQueue implements QueueContract
{
    use InteractsWithTime;

    /**
     * @var string
     */
    protected $projectId;
    /**
     * @var string
     */
    protected $location;
    /**
     * @var string
     */
    protected $defaultQueue;
    /**
     * @var Dispatcher
     */
    protected $client;
    /**
     * @var TaskFactory
     */
    private $factory;

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
    public function size($queue = null)
    {
        return 0;
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
    public function push($job, $data = '', $queue = null)
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
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $id = json_decode($payload, true)['id'] ?? null;

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
    public function later($delay, $job, $data = '', $queue = null)
    {
        $this->checkDelayValueIsValid($delay);
        $scheduledAt = $this->availableAt($delay);
        $payload = $this->createPayload($job, $this->getQueue($queue));

        $id = json_decode($payload, true)['id'] ?? null;

        $this->pushGoogleTask($this->getQueue($queue), $payload, $id, $scheduledAt);

        return $id;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return BaseJob|null
     */
    public function pop($queue = null)
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
    protected function createPayloadArray($job, $queue, $data = '')
    {
        return array_merge(parent::createPayloadArray($job, $queue, $data), [
            'id' => $this->getRandomId($job),
            'attempts' => 0,
        ]);
    }

    /**
     * Get a random ID string.
     *
     * @param object $job
     * @return string
     */
    protected function getRandomId(object $job)
    {
        return str_replace(['_', '\\'], ['__', '_'], get_class($job)) . '-' .
            now()->format('Ymdhis') . '-' . Str::random(16);
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?? $this->defaultQueue;
    }

    /**
     * @param $queue
     * @param $payload
     * @param $name
     * @param DateInterval|DateTimeInterface|int|null $scheduledAt
     *
     */
    protected function pushGoogleTask(string $queue, string $payload, $name, $scheduledAt = null)
    {
        $this->client->dispatch($name, $this->getConnectionName(), $payload, $scheduledAt, $queue);
    }

    /**
     * @param DateInterval|DateTimeInterface|int $delay
     */
    protected function checkDelayValueIsValid($delay)
    {
        if (
            $delay !== null &&
            ! $delay instanceof DateTimeInterface &&
            ! $delay instanceof DateInterval &&
            ! (is_int($delay) && $delay > 0)
        ) {
            $type = gettype($delay);
            if (is_object($delay)) {
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
