<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Google\Cloud\Tasks\V2beta3\AppEngineHttpRequest;
use Google\Cloud\Tasks\V2beta3\HttpMethod;
use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Illuminate\Contracts\Routing\UrlGenerator;

class RequestGenerator
{
    /**
     * @var UrlGenerator
     */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function forAppEngine(string $payload, string $connection) : AppEngineHttpRequest
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

    public function forHttpHandler(string $payload, string $connection) : HttpRequest
    {
        return (new HttpRequest())
            ->setHttpMethod(HttpMethod::POST)
            ->setBody($payload)
            ->setUrl(
                $this->generator->route(
                    'google.tasks',
                    ['connection' => $connection],
                    true
                )
            );
    }
}
