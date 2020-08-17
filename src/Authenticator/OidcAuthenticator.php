<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Authenticator;

use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Google\Cloud\Tasks\V2beta3\OidcToken;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;

class OidcAuthenticator implements HttpRequestAuthenticator
{
    /**
     * @var string
     */
    private $serviceAccountEmail;
    /**
     * @var string
     */
    private $audience;

    public function __construct(string $serviceAccountEmail, ?string $audience = null)
    {
        $this->serviceAccountEmail = $serviceAccountEmail;
        $this->audience = $audience;
    }

    public function addAuthentication(HttpRequest $request) : HttpRequest
    {
        return $request->setOidcToken(new OidcToken(array_filter([
            'service_account_email' => $this->serviceAccountEmail,
            'audience' => $this->audience,
        ])));
    }
}
