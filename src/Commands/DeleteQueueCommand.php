<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class DeleteQueueCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:delete {name} {--force}';
    protected $description = 'Deletes a google tasks queue';

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function action(CloudTasksClient $client, array $config, string $namespaceName, string|null $queueName): int
    {
        $this->warn(
            <<<WARN
If you delete a queue from the Cloud Console, you must wait 7 days before recreating it with the same name.
Doing so protects against unexpected behaviour in tasks that are currently executing or waiting to be executed
and also avoids other failures in the delete/recreate cycle that can occur due to periodic internal processes.
WARN
        );
        if (
            ! $this->option('force') &&
            ! $this->confirm('This action will delete all unfinished tasks permanently, do you wish to continue?')
        ) {
            return 0;
        }
        $queueName = CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        );
        $client->deleteQueue($queueName);
        $this->line("Queue {$namespaceName} has been deleted.");

        return 0;
    }
}
