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

    public function __construct(protected UrlGenerator $generator, protected Container $container)
    {
    }

    public function forAppEngine(string $payload, string $connection, string $queue): AppEngineHttpRequest
    {
        return (new AppEngineHttpRequest())
            ->setHttpMethod(HttpMethod::POST)
            ->setBody($payload)
            ->setRelativeUri(
                $this->generator->route(
                    'google.tasks',
                    ['connection' => $connection, 'queue' => $queue],
                    false
                )
            );
    }

    public function forHttpHandler(string $payload, string $connection, string $queue): HttpRequest
    {
        return (new HttpRequest())
            ->setHttpMethod(HttpMethod::POST)
            ->setBody($payload)
            ->setUrl(
                $this->cloudTasksUrl($connection, $queue)
            );
    }

    protected function cloudTasksUrl(string $connection, string $queue): string
    {
        $config = $this->getConfig($connection);

        if ($config['domain'] ?? false) {
            return 'https://' . $config['domain'] .
                $this->generator->route(
                    'google.tasks',
                    ['connection' => $connection, 'queue' => $queue],
                    false
                );
        }

        return $this->generator->secure(
            $this->generator->route(
                'google.tasks',
                ['connection' => $connection, 'queue' => $queue],
                false
            )
        );
    }
}
