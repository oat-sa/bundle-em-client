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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http;

use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use OAT\Bundle\EnvironmentManagementClientBundle\EventSubscriber\HttpRequestSecuritySubscriber;
use OAT\Library\EnvironmentManagementClient\Exception\TokenUnauthorizedException;
use OAT\Library\EnvironmentManagementClient\Http\JWTTokenExtractorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpRequestSecuritySubscriberTest extends TestCase
{
    private HttpRequestSecuritySubscriber $subject;

    /** @var JWTTokenExtractorInterface|MockObject  */
    private JWTTokenExtractorInterface $jwtTokenExtractorMock;

    /** @var HttpMessageFactoryInterface|MockObject */
    private HttpMessageFactoryInterface $httpMessageFactoryMock;

    protected function setUp(): void
    {
        $this->jwtTokenExtractorMock = $this->createMock(JWTTokenExtractorInterface::class);
        $this->httpMessageFactoryMock = $this->createMock(HttpMessageFactoryInterface::class);

        $this->subject = new HttpRequestSecuritySubscriber(
            $this->jwtTokenExtractorMock,
            $this->httpMessageFactoryMock,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                KernelEvents::REQUEST => [
                    ['validateRouteOauth2Scopes', 10],
                ],
            ],
            $this->subject->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider validateRouteOauth2ScopesDataProvider
     */
    public function testValidateRouteOauth2Scopes(
        ?array $allowedScopes,
        string|array $tokenScopes,
        ?string $expectedExceptionMessage,
    ): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag([
            'em_oauth2_scopes' => $allowedScopes,
        ]);
        $token = new Plain(
            new DataSet([], ''),
            new DataSet(['scopes' => $tokenScopes], ''),
            new Signature('foo', 'bar'),
        );
        $psrRequestMock = $this->createMock(ServerRequestInterface::class);
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        $this->httpMessageFactoryMock
            ->method('createRequest')
            ->with($request)
            ->willReturn($psrRequestMock);

        $this->jwtTokenExtractorMock
            ->method('extract')
            ->willReturn($token);

        if ($expectedExceptionMessage) {
            $this->expectException(UnauthorizedHttpException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $this->subject->validateRouteOauth2Scopes(new RequestEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        ));
    }

    public function validateRouteOauth2ScopesDataProvider(): array
    {
        return [
            'scopes are defined for path and they are in the token' => [
                ['scope1', 'scope2'],
                ['scope1', 'scope2'],
                null,
            ],
            'scopes are defined for path and they are not in the token' => [
                ['scope1', 'scope2'],
                [],
                'Invalid scope(s)',
            ],
            'scopes are not defined for path and they are in the token' => [
                null,
                ['scope1', 'scope2'],
                null,
            ],
            'scopes are not defined for path and they are not in the token' => [
                null,
                [],
                null,
            ],
            'scopes are not defined for path and there are invalid scopes in the token' => [
                ['scope1', 'scope2'],
                ['scope3'],
                'Invalid scope(s)',
            ],
            'scopes defined as string' => [
                ['scope1', 'scope2'],
                'scope1 scope2',
                null,
            ],
        ];
    }

    public function testValidateRouteOauth2ScopesWithSubRequest(): void
    {
        $request = $this->createMock(Request::class);
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        $this->httpMessageFactoryMock
            ->expects($this->never())
            ->method('createRequest');

        $this->jwtTokenExtractorMock
            ->expects($this->never())
            ->method('extract');

        $this->subject->validateRouteOauth2Scopes(new RequestEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::SUB_REQUEST,
        ));
    }

    public function testValidateRouteOauth2ScopesWithNoToken(): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag([
            'em_oauth2_scopes' => ['scope1', 'scope2'],
        ]);

        $psrRequestMock = $this->createMock(ServerRequestInterface::class);
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        $this->httpMessageFactoryMock
            ->method('createRequest')
            ->with($request)
            ->willReturn($psrRequestMock);

        $this->jwtTokenExtractorMock
            ->method('extract')
            ->willThrowException(new TokenUnauthorizedException('reason'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('reason');

        $this->subject->validateRouteOauth2Scopes(new RequestEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        ));
    }
}
