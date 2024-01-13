<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http\Client;

use OAT\Bundle\EnvironmentManagementClientBundle\Http\Client\EnvironmentManagementAwareHttpClient;
use OAT\Bundle\EnvironmentManagementClientBundle\Http\Client\EnvironmentManagementAwareHttpClientFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EnvironmentManagementAwareHttpClientFactoryTest extends TestCase
{
    private EnvironmentManagementAwareHttpClientFactory $subject;
    private CacheInterface|MockObject $cacheMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->subject = new EnvironmentManagementAwareHttpClientFactory($this->cacheMock);
    }

    public function testCreate(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);

        $result = $this->subject->create(
            $httpClientMock,
            'http://auth.server',
            '/token',
            ['credentials'],
        );

        $this->assertInstanceOf(EnvironmentManagementAwareHttpClient::class, $result);
        $this->assertInstanceOf(HttpClientInterface::class, $result);
    }
}