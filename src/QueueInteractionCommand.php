<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\QueueManager;

abstract class QueueInteractionCommand extends Command
{
    use ConnectionRetrieval;

    public function handle(Container $container, QueueManager $manager): int
    {
        [$connectionName, $queueName] = array_pad(explode(':', $this->argument('name')), 2, null);
        $queue = $manager->connection($connectionName);
        $config = $this->getConfig($connectionName);
        $namespaceName = implode(':', [$connectionName, $queueName ?? $config['queue']]);

        if (! $queue instanceof Queue) {
            throw new \LogicException();
        }

        $client = $queue->client();

        return (int) $container->call([$this, 'action'], [
            'name' => $connectionName,
            'namespaceName' => $namespaceName,
            'queueName' => $queueName,
            'client' => $client,
            'config' => $config,
            'queue' => $queue,
        ]);
    }
}
