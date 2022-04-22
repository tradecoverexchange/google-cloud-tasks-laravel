<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Google\Cloud\Tasks\V2beta3\Queue as CloudQueue;
use Google\Cloud\Tasks\V2beta3\RateLimits;
use Google\Cloud\Tasks\V2beta3\RetryConfig;
use Google\Protobuf\Duration;

class CloudQueueModifier
{
    public function apply(CloudQueue $queue, array $config): CloudQueue
    {
        if ($config['rate_limits'] ?? false) {
            $queue->setRateLimits(new RateLimits($config['rate_limits']));
        }
        if ($config['retry_config'] ?? false) {
            $queue->setRetryConfig(new RetryConfig(
                collect($config['retry_config'])
                    ->map(function ($value, $key) {
                        if (in_array($key, ['max_backoff', 'min_backoff', 'max_retry_duration'])) {
                            return new Duration($value);
                        }

                        return $value;
                    })
                    ->all()
            ));
        }

        return $queue;
    }
}
