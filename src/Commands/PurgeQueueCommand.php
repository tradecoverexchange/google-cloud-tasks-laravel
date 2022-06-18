<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class PurgeQueueCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:clear {name} {--force}';
    protected $description = 'Purges a google tasks queue';

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function action(CloudTasksClient $client, string $namespaceName, string|null $queueName, array $config): int
    {
        if (
            ! $this->option('force') &&
            ! $this->confirm('This action will delete all unfinished tasks permanently, do you wish to continue?')
        ) {
            return 0;
        }
        $cloudQueueName = CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        );
        $client->purgeQueue($cloudQueueName);
        $this->line("Queue {$namespaceName} is being purged.");

        return 0;
    }
}
