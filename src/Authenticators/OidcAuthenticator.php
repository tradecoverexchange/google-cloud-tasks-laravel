<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Authenticators;

use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Google\Cloud\Tasks\V2beta3\OidcToken;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;

class OidcAuthenticator implements HttpRequestAuthenticator
{
    public function __construct(protected string $serviceAccountEmail, protected string|null $audience = null)
    {
    }

    public function addAuthentication(HttpRequest $request): HttpRequest
    {
        return $request->setOidcToken(new OidcToken(array_filter([
            'service_account_email' => $this->serviceAccountEmail,
            'audience' => $this->audience,
        ])));
    }
}
