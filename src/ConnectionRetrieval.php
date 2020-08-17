<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;

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
        $container = $this->container ?? app()->make(Container::class);
        if ($name) {
            return $container->make(Repository::class)->get("queue.connections.{$name}");
        }

        return ['driver' => 'null'];
    }
}
