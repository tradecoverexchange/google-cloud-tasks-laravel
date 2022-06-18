<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Commands;

use Google\Cloud\Tasks\V2beta3\CloudTasksClient;
use Google\Cloud\Tasks\V2beta3\Queue\State;
use Illuminate\Support\Str;
use TradeCoverExchange\GoogleCloudTaskLaravel\QueueInteractionCommand;

class QueueStatusCommand extends QueueInteractionCommand
{
    protected $signature = 'google:cloud:queue:status {name} {--toggle}';

    protected $description = 'Can retrieve and toggle the status of a google tasks queue';

    public static array $states = [
        State::RUNNING => State::PAUSED,
        State::PAUSED => State::RUNNING,
    ];

    /**
     * @throws \Google\ApiCore\ApiException
     */
    public function action(CloudTasksClient $client, string $namespaceName, string|null $queueName, array $config): int
    {
        $shouldToggle = (bool) $this->option('toggle');
        $cloudQueueName = CloudTasksClient::queueName(
            $config['project_id'],
            $config['location'],
            $queueName ?? $config['queue']
        );
        $cloudQueue = $client->getQueue($cloudQueueName);
        $this->line(sprintf(
            'Queue %s is %s',
            $namespaceName,
            Str::lower(State::name($cloudQueue->getState())),
        ));
        if (in_array($cloudQueue->getState(), [State::DISABLED, State::STATE_UNSPECIFIED]) && $shouldToggle) {
            $this->line("Queue {$namespaceName} cannot be toggled in it's current state");

            return 1;
        }
        if ($shouldToggle) {
            $cloudQueue->setState(self::$states[$cloudQueue->getState()]);
            $client->updateQueue($cloudQueue);
            $this->line(sprintf(
                'Queue %s is now %s',
                $namespaceName,
                Str::lower(State::name($cloudQueue->getState()))
            ));
        }

        return 0;
    }
}
