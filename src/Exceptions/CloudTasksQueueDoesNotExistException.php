<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Exceptions;

use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Google\ApiCore\ApiException;

class CloudTasksQueueDoesNotExistException extends \Exception implements ProvidesSolution
{
    public function __construct(protected string $connection, protected string $queue, ApiException $previous)
    {
        parent::__construct($previous->message, code: $previous->code, previous: $previous);
    }

    public function getSolution(): Solution
    {
        return new class($this->connection, $this->queue) implements Solution {

            public function __construct(protected string $connection, protected string $queue)
            {
            }

            public function getSolutionTitle(): string
            {
                return 'Google Cloud Tasks Queue has not been created.';
            }

            public function getSolutionDescription(): string
            {
                return 'All queues must exist before a Google Cloud Task can be dispatched to the queue.' . PHP_EOL
                    . sprintf(
                        'You may create a new queue within the artisan queue using `php artisan google:cloud:queue:create %s:%s`.',
                        $this->connection,
                        $this->queue,
                    );
            }

            public function getDocumentationLinks(): array
            {
                return [
                    'Google Cloud - Cloud Tasks - Creating Queues' => 'https://cloud.google.com/tasks/docs/creating-queues'
                ];
            }
        };
    }
}
