<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;

trait ConnectionRetrieval
{
    /**
     * Get the queue connection configuration.
     *
     * @param string $name
     * @return array
     * @throws BindingResolutionException
     */
    protected function getConfig($name)
    {
        if ($name) {
            return $this->container->make(Repository::class)->get("queue.connections.{$name}");
        }

        return ['driver' => 'null'];
    }
}
