<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Exceptions;

use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Google\ApiCore\ApiException;

class ServiceEmailNotSetException extends \Exception implements ProvidesSolution
{
    public function __construct(protected string $connection, protected string $queue, ApiException $previous)
    {
        parent::__construct($previous->message, code: $previous->code, previous: $previous);
    }

    public function getSolution(): Solution
    {
        return new class ($this->connection) implements Solution {
            public function __construct(protected string $connection)
            {
            }

            public function getSolutionTitle(): string
            {
                return 'Google Cloud Tasks driver is not configured fully.';
            }

            public function getSolutionDescription(): string
            {
                return 'All Google Cloud tasks are send with a signed JWT.' . PHP_EOL
                    . 'The service email property of all HTTP task queues must be configured before dispatching a task, in this case '
                    . sprintf(
                        '`queue.connections.%s.authentication.service_account`.',
                        $this->connection,
                    );
            }

            public function getDocumentationLinks(): array
            {
                return [];
            }
        };
    }
}
