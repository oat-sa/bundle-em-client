<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\Client;

use InvalidArgumentException;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class EnvironmentManagementAwareHttpClient implements HttpClientInterface
{
    public const OPTION_TENANT_ID = 'tenantId';
    public const OPTION_SCOPES = 'scopes';
    public const OPTION_AUTH_SERVER_REQUEST_TIMEOUT = 'authServerRequestTimeout';
    private const ACCESS_TOKEN_CACHE_KEY = 'em_http_client_access_token_%s';
    private const DEFAULT_AUTH_SERVER_REQUEST_TIMEOUT = null;

    public function __construct(
        private CacheInterface $cache,
        private HttpClientInterface $decoratedHttpClient,
        private string $authServerHost,
        private string $authServerTokenRequestPath,
        private array $oauth2ClientCredentials,
    ) {}

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->decoratedHttpClient->stream($responses, $timeout);
    }

    /**
     * Requests an HTTP resource.
     * @see HttpClientInterface::request()
     *
     * Two additional options are available:
     * - EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID: The tenant id to be used for the authentication
     * - EnvironmentManagementAwareHttpClient::OPTION_SCOPES: The scopes to be used for the access token
     *
     * @throws CacheInvalidArgumentException
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (!array_key_exists(self::OPTION_TENANT_ID, $options)) {
            throw new InvalidArgumentException(sprintf(
                'The following key is missing from the `options` parameter of the request: %s',
                self::OPTION_TENANT_ID,
            ));
        }

        $tenantId = $options[self::OPTION_TENANT_ID];

        $scopes = array_key_exists(self::OPTION_SCOPES, $options)
            ? is_array($options[self::OPTION_SCOPES]) ? $options[self::OPTION_SCOPES] : []
            : [];

        $authServerRequestTimeout = array_key_exists(self::OPTION_AUTH_SERVER_REQUEST_TIMEOUT, $options)
            ? (float) $options[self::OPTION_AUTH_SERVER_REQUEST_TIMEOUT]
            : self::DEFAULT_AUTH_SERVER_REQUEST_TIMEOUT;

        unset($options[self::OPTION_TENANT_ID]);
        unset($options[self::OPTION_SCOPES]);
        unset($options[self::OPTION_AUTH_SERVER_REQUEST_TIMEOUT]);

        return $this->decoratedHttpClient->request($method, $url, array_merge($options, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->getToken($tenantId, $scopes, $authServerRequestTimeout)),
            ],
        ]));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    private function getToken(string $tenantId, array $scopes = [], ?float $authServerRequestTimeout = null): string
    {
        return $this->cache->get(
            sprintf(self::ACCESS_TOKEN_CACHE_KEY, $tenantId),
            function(ItemInterface $item) use ($tenantId, $scopes, $authServerRequestTimeout) {
                $oauth2Credentials = current(array_filter($this->oauth2ClientCredentials, function (array $credentials) use ($tenantId) {
                    return ($credentials['tenantId'] ?? null) === $tenantId;
                }));

                if (!$oauth2Credentials) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'No OAuth2 credentials found for tenant %s',
                            $tenantId
                        )
                    );
                }

                if (!array_key_exists('clientId', $oauth2Credentials) || !array_key_exists('clientSecret', $oauth2Credentials)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'No OAuth2 client ID and/or client secret found for tenant %s',
                            $tenantId
                        )
                    );
                }

                $response = $this->decoratedHttpClient->request(
                    'POST',
                    $this->authServerHost . $this->authServerTokenRequestPath,
                    [
                        'form_params' => [
                            'grant_type' => 'client_credentials',
                            'client_id' => $oauth2Credentials['clientId'],
                            'client_secret' => $oauth2Credentials['clientSecret'],
                            'scope' => implode(' ', $scopes),
                        ],
                        'timeout' => $authServerRequestTimeout,
                    ],
                );

                $responsePayload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

                if ($response->getStatusCode() !== 200) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to get access token for tenant %s. Reason: %s',
                            $tenantId,
                            $responsePayload['message'] ?? 'Unknown'
                        )
                    );
                }

                $item->expiresAfter($responsePayload['expires_in']);

                return $responsePayload['access_token'];
            }
        );
    }
}
