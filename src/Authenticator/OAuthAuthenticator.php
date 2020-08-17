<?php

namespace TradeCoverExchange\GoogleCloudTaskLaravel\Authenticator;

use Google\Cloud\Tasks\V2beta3\HttpRequest;
use Google\Cloud\Tasks\V2beta3\OAuthToken;
use TradeCoverExchange\GoogleCloudTaskLaravel\HttpRequestAuthenticator;

class OAuthAuthenticator implements HttpRequestAuthenticator
{
    /**
     * @var string
     */
    private $serviceAccountEmail;
    /**
     * @var string|null
     */
    private $scope;

    public function __construct(string $serviceAccountEmail, ?string $scope = null)
    {
        $this->serviceAccountEmail = $serviceAccountEmail;
        $this->scope = $scope;
    }

    public function addAuthentication(HttpRequest $request) : HttpRequest
    {
        return $request->setOauthToken(new OAuthToken(array_filter([
            'service_account_email' => $this->serviceAccountEmail,
            'scope' => $this->scope,
        ])));
    }
}
