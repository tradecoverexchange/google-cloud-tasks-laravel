<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Google\Cloud\Tasks\V2beta3\AppEngineHttpRequest;
use Google\Cloud\Tasks\V2beta3\HttpMethod;
use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;

class RequestGenerator
{
    use ConnectionRetrieval;

    /**
     * @var UrlGenerator
     */
    private $generator;
    /**
     * @var Container
     */
    private $container;

    public function __construct(UrlGenerator $generator, Container $container)
    {
        $this->generator = $generator;
        $this->container = $container;
    }

    public function forAppEngine(string $payload, string $connection): AppEngineHttpRequest
    {
        return (new AppEngineHttpRequest())
            ->setHttpMethod(HttpMethod::POST)
            ->setBody($payload)
            ->setRelativeUri(
                $this->generator->route(
                    'google.tasks',
                    ['connection' => $connection],
                    false
                )
            );
    }

    public function forHttpHandler(string $payload, string $connection): HttpRequest
    {
        return (new HttpRequest())
            ->setHttpMethod(HttpMethod::POST)
            ->setBody($payload)
            ->setUrl(
                //TODO generate domain for url or use current host
                $this->cloudTasksUrl($connection)
            );
    }

    protected function cloudTasksUrl($connection): string
    {
        $config = $this->getConfig($connection);

        if ($config['domain'] ?? false) {
            return 'https://' . $config['domain'] .
                $this->generator->route(
                    'google.tasks',
                    ['connection' => $connection],
                    false
                );
        }

        return $this->generator->secure(
            $this->generator->route(
                'google.tasks',
                ['connection' => $connection],
                false
            )
        );
    }
}
