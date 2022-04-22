<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Factories;

use Google\ApiCore\ValidationException;
use Google\Cloud\Tasks\V2beta3\CloudTasksClient;

class CloudTaskClientFactory
{
    /**
     * @param array $options
     * @return CloudTasksClient
     * @throws ValidationException
     */
    public function make(array $options): CloudTasksClient
    {
        return new CloudTasksClient($options);
    }
}
