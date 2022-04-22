<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Dispatchers;

use Google\ApiCore\ApiException;
use TradeCoverExchange\GoogleCloudTaskLaravel\Exceptions\CloudTasksQueueDoesNotExistException;
use TradeCoverExchange\GoogleCloudTaskLaravel\Exceptions\ServiceEmailNotSetException;

trait BreakoutApiErrors
{
    protected function breakoutException(ApiException $exception, string $connection, string $queueName): \Exception|null
    {
        $error = json_decode($exception->getMessage(), true);
        if ($error['message'] === 'service_account_email must be set.') {
            return new ServiceEmailNotSetException($connection, $queueName, $exception);
        }
        if ($error['message'] === 'Queue does not exist.') {
            return new CloudTasksQueueDoesNotExistException($connection, $queueName, $exception);
        }

        return null;
    }
}
