<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http\Client;

use InvalidArgumentException;
use OAT\Bundle\EnvironmentManagementClientBundle\Http\Client\EnvironmentManagementAwareHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class EnvironmentManagementAwareHttpClientTest extends TestCase
{
    private EnvironmentManagementAwareHttpClient $subject;
    private HttpClientInterface|MockObject $httpClientMock;
    private string $authServerHost = 'http://auth.server';
    private string $authServerTokenRequestPath = '/token';
    private array $oauth2ClientCredentials = [
        ['tenantId' => '1', 'clientId' => 'clientId1', 'clientSecret' => 'clientSecret1'],
        ['tenantId' => '2', 'clientId' => 'clientId2', 'clientSecret' => 'clientSecret2'],
        ['tenantId' => '3'],
    ];

    protected function setUp(): void
    {
        $cache = new NullAdapter();
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->subject = new EnvironmentManagementAwareHttpClient(
            $cache,
            $this->httpClientMock,
            $this->authServerHost,
            $this->authServerTokenRequestPath,
            $this->oauth2ClientCredentials,
        );
    }

    public function testStream(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responses = [$responseMock];
        $timeout = 1.0;
        $responseStreamMock = $this->createMock(ResponseStreamInterface::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('stream')
            ->with($responses, $timeout)
            ->willReturn($responseStreamMock);

        $this->assertSame($responseStreamMock, $this->subject->stream($responses, $timeout));
    }

    public function testRequestWithValidTenantId(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '1'];
        $responseMock = $this->createMock(ResponseInterface::class);
        $authServerResponseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->authServerHost . $this->authServerTokenRequestPath,
                    $this->callback(function ($arrayParam) {
                        $formFields = [
                            'grant_type' => 'client_credentials',
                            'client_id' => 'clientId1',
                            'client_secret' => 'clientSecret1',
                            'scope' => '',
                        ];

                        $formData = new FormDataPart($formFields);

                        return
                            array_key_exists('body', $arrayParam)
                            && $arrayParam['body'] == $formData->bodyToIterable()
                            && array_key_exists('headers', $arrayParam)
                            && str_contains($arrayParam['headers'][0], 'Content-Type: multipart/form-data; boundary=')
                            && array_key_exists('timeout', $arrayParam)
                            && $arrayParam['timeout'] === null;
                    })
                ],
                [$method, $url, ['headers' => ['Authorization' => 'Bearer token']]],
            )
            ->willReturnOnConsecutiveCalls(
                $authServerResponseMock,
                $responseMock,
            );

        $authServerResponseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $authServerResponseMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"access_token": "token", "expires_in": 3600}');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithValidTenantIdAndScope(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [
            EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '1',
            EnvironmentManagementAwareHttpClient::OPTION_SCOPES => ['foo', 'bar'],
        ];
        $responseMock = $this->createMock(ResponseInterface::class);
        $authServerResponseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->authServerHost . $this->authServerTokenRequestPath,
                    $this->callback(function ($arrayParam) {
                        $formFields = [
                            'grant_type' => 'client_credentials',
                            'client_id' => 'clientId1',
                            'client_secret' => 'clientSecret1',
                            'scope' => 'foo bar',
                        ];

                        $formData = new FormDataPart($formFields);

                        return
                            array_key_exists('body', $arrayParam)
                            && $arrayParam['body'] == $formData->bodyToIterable()
                            && array_key_exists('headers', $arrayParam)
                            && str_contains($arrayParam['headers'][0], 'Content-Type: multipart/form-data; boundary=')
                            && array_key_exists('timeout', $arrayParam)
                            && $arrayParam['timeout'] === null;
                    })
                ],
                [$method, $url, ['headers' => ['Authorization' => 'Bearer token']]],
            )
            ->willReturnOnConsecutiveCalls(
                $authServerResponseMock,
                $responseMock,
            );

        $authServerResponseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $authServerResponseMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"access_token": "token", "expires_in": 3600}');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithoutTenantId(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [];
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The following key is missing from the `options` parameter of the request: tenantId');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithNonExistingTenantId(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [
            EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '4',
        ];
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No OAuth2 credentials found for tenant 4');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithInvalidTenantId(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [
            EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '3',
        ];
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No OAuth2 client ID and/or client secret found for tenant 3');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithTokenRequestFailure(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '1'];
        $responseMock = $this->createMock(ResponseInterface::class);
        $authServerResponseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->authServerHost . $this->authServerTokenRequestPath,
                    $this->callback(function ($arrayParam) {
                        $formFields = [
                            'grant_type' => 'client_credentials',
                            'client_id' => 'clientId1',
                            'client_secret' => 'clientSecret1',
                            'scope' => '',
                        ];

                        $formData = new FormDataPart($formFields);

                        return
                            array_key_exists('body', $arrayParam)
                            && $arrayParam['body'] == $formData->bodyToIterable()
                            && array_key_exists('headers', $arrayParam)
                            && str_contains($arrayParam['headers'][0], 'Content-Type: multipart/form-data; boundary=')
                            && array_key_exists('timeout', $arrayParam)
                            && $arrayParam['timeout'] === null;
                    })
                ],
                [$method, $url, ['headers' => ['Authorization' => 'Bearer token']]],
            )
            ->willReturnOnConsecutiveCalls(
                $authServerResponseMock,
                $responseMock,
            );

        $authServerResponseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(500);

        $authServerResponseMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"message":"reason"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get access token for tenant 1. Reason: reason');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }

    public function testRequestWithAuthServerRequestTimeoutOption(): void
    {
        $method = 'method';
        $url = 'url';
        $options = [
            EnvironmentManagementAwareHttpClient::OPTION_TENANT_ID => '1',
            EnvironmentManagementAwareHttpClient::OPTION_AUTH_SERVER_REQUEST_TIMEOUT => 2.5,
        ];
        $responseMock = $this->createMock(ResponseInterface::class);
        $authServerResponseMock = $this->createMock(ResponseInterface::class);

        $this->httpClientMock
            ->method('request')
            ->withConsecutive(
                [
                    'POST',
                    $this->authServerHost . $this->authServerTokenRequestPath,
                    $this->callback(function ($arrayParam) {
                        $formFields = [
                            'grant_type' => 'client_credentials',
                            'client_id' => 'clientId1',
                            'client_secret' => 'clientSecret1',
                            'scope' => '',
                        ];

                        $formData = new FormDataPart($formFields);

                        return
                            array_key_exists('body', $arrayParam)
                            && $arrayParam['body'] == $formData->bodyToIterable()
                            && array_key_exists('headers', $arrayParam)
                            && str_contains($arrayParam['headers'][0], 'Content-Type: multipart/form-data; boundary=')
                            && array_key_exists('timeout', $arrayParam)
                            && $arrayParam['timeout'] === 2.5;
                    })
                ],
                [$method, $url, ['headers' => ['Authorization' => 'Bearer token']]],
            )
            ->willReturnOnConsecutiveCalls(
                $authServerResponseMock,
                $responseMock,
            );

        $authServerResponseMock
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $authServerResponseMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"access_token": "token", "expires_in": 3600}');

        $this->assertSame($responseMock, $this->subject->request($method, $url, $options));
    }
}
