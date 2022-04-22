<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Illuminate\Config\Repository;

use Illuminate\Contracts\Container\Container;

trait ConnectionRetrieval
{
    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getConfig(string $name): array
    {
        $container = property_exists($this, 'container') && $this->container instanceof Container
            ? $this->container
            : \Illuminate\Container\Container::getInstance();

        if ($container->make(Repository::class)->has("queue.connections.{$name}")) {
            return $container->make(Repository::class)->get("queue.connections.{$name}");
        }

        return ['driver' => 'null'];
    }
}
