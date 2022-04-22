<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

class ConfigureUrlGenerator
{
    public function __construct(protected Container $container)
    {
    }

    public function handle(Request $request, callable $next): mixed
    {
        // This is done to make the URL generator use the config `app.url` value for URL
        // generation, otherwise the domain of the incoming request would be used but in some applications
        // this might be an issue as tasks are sent to a different host.

        // Normally a queue process is run by the CLI, setting the request like this will
        // make the URL generator behave similar to how it would in CLI mode.
        $this->container->make('url')->setRequest($this->makeRequest());

        return $next($request);
    }

    protected function makeRequest(): Request
    {
        $uri = $this->container->make('config')->get('app.url', 'http://localhost');

        $components = parse_url($uri);

        $server = $_SERVER;

        if (isset($components['path'])) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $components['path'],
                'SCRIPT_NAME' => $components['path'],
            ]);
        }

        return Request::create(
            $uri,
            'GET',
            [],
            [],
            [],
            $server
        );
    }
}
