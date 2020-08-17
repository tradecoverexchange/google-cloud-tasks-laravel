<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Middlewares;

use Closure;
use Illuminate\Http\Response;

class AppEngine
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        abort_if(! $request->hasHeader('X-AppEngine-QueueName'), Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
