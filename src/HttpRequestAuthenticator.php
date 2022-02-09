<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel;

use Google\Cloud\Tasks\V2beta3\HttpRequest;

interface HttpRequestAuthenticator
{
    public function addAuthentication(HttpRequest $request): HttpRequest;
}
