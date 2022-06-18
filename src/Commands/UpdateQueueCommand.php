<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue;
use TradeCoverExchange\GoogleCloudTaskLaravel\CloudQueueModifier;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class UpdateQueueCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:update {name}';
    protected $description = 'Updates the settings for a google tasks queue';

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function action(
        string $namespaceName,
        string|null $queueName,
        CloudTasksClient $client,
        array $config,
        CloudQueueModifier $queueModifier
    ): int {
        $cloudQueueName = CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        );
        $cloudQueue = new Queue();
        $cloudQueue->setName($cloudQueueName);
        $cloudQueue = $queueModifier->apply($cloudQueue, $config['settings'] ?? []);
        $client->updateQueue($cloudQueue);
        $this->line("Queue {$namespaceName} has been updated.");

        return 0;
    }
}
