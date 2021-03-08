<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Response;
use TradeCoverExchange\GoogleCloudTaskLaravel\ConnectionRetrieval;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\AppEngineConnector;
use TradeCoverExchange\GoogleCloudTaskLaravel\Connectors\CloudTasksConnector;
use TradeCoverExchange\GoogleJwtVerifier\Laravel\AuthenticateByOidc;

class CloudTasks
{
    use ConnectionRetrieval;

    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            ($connection = $request->route()->parameter('connection')) &&
            is_string($connection) &&
            ($config = $this->getConfig($connection))
        ) {
            if (
                $config['driver'] === CloudTasksConnector::DRIVER &&
                data_get($config, 'authentication.token_type') === 'oidc'
            ) {
                return $this->container->make(AuthenticateByOidc::class)->handle(
                    $request,
                    $next,
                    data_get($config, 'authentication.service_account', '')
                );
            } elseif ($config['driver'] === AppEngineConnector::DRIVER) {
                return $this->container->make(AppEngine::class)->handle(
                    $request,
                    $next,
                );
            }
        }

        abort(Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
