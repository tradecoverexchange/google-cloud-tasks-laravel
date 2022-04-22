<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Authenticators;

use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Google\Cloud\Tasks\V2beta3\OAuthToken;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;

class OAuthAuthenticator implements HttpRequestAuthenticator
{
    public function __construct(protected string $serviceAccountEmail, protected string|null $scope = null)
    {
    }

    public function addAuthentication(HttpRequest $request): HttpRequest
    {
        return $request->setOauthToken(new OAuthToken(array_filter([
            'service_account_email' => $this->serviceAccountEmail,
            'scope' => $this->scope,
        ])));
    }
}
