<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\Client;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EnvironmentManagementAwareHttpClientFactory
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    /**
     * Creates a new HttpClient that decorates the one passed as parameter. The decorated HttpClient will
     * - authenticates against the Environment Management's Auth Server based on the provided tenant id and scopes
     * - caches the access tokens
     *
     * @param HttpClientInterface $decoratedHttpClient
     * @param string $authServerHost
     * @param string $authServerTokenRequestPath
     * @param array $oauth2ClientCredentials
     * @return EnvironmentManagementAwareHttpClient
     */
    public function create(
        HttpClientInterface $decoratedHttpClient,
        string $authServerHost,
        string $authServerTokenRequestPath,
        array $oauth2ClientCredentials,
    ): EnvironmentManagementAwareHttpClient {
        return new EnvironmentManagementAwareHttpClient(
            $this->cache,
            $decoratedHttpClient,
            $authServerHost,
            $authServerTokenRequestPath,
            $oauth2ClientCredentials,
        );
    }
}
