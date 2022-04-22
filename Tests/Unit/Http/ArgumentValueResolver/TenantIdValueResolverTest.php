<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http\ArgumentValueResolver;

use Generator;
use OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver\TenantIdValueResolver;
use OAT\Library\EnvironmentManagementClient\Exception\TenantIdNotFoundException;
use OAT\Library\EnvironmentManagementClient\Http\TenantIdExtractorInterface;
use OAT\Library\EnvironmentManagementClient\Model\FeatureFlagCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class TenantIdValueResolverTest extends TestCase
{
    private MockObject|HttpMessageFactoryInterface $messageFactoryMock;
    private TenantIdExtractorInterface|MockObject $tenantIdExtractorMock;
    private TenantIdValueResolver $subject;

    protected function setUp(): void
    {
        $this->messageFactoryMock = $this->createMock(HttpMessageFactoryInterface::class);
        $this->tenantIdExtractorMock = $this->createMock(TenantIdExtractorInterface::class);

        $this->subject = new TenantIdValueResolver($this->messageFactoryMock, $this->tenantIdExtractorMock);
    }

    public function testSupports(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);

        $metadataMock->expects($this->once())
            ->method('getType')
            ->willReturn('string');

        $metadataMock->expects($this->once())
            ->method('getName')
            ->willReturn('tenantId');

        $this->assertTrue($this->subject->supports(new Request(), $metadataMock));
    }

    public function testResolveForSuccess(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);
        $request = new Request();
        $psrRequest = $this->createMock(ServerRequestInterface::class);

        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')
            ->with($request)
            ->willReturn($psrRequest);

        $this->tenantIdExtractorMock->expects($this->once())
            ->method('extract')
            ->with($psrRequest)
            ->willReturn('tenant-1');

        $generator = $this->subject->resolve($request, $metadataMock);
        $this->assertInstanceOf(Generator::class, $generator);
        $this->assertEquals('tenant-1', $generator->current());
    }
}
