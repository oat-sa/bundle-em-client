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

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http;

use OAT\Bundle\EnvironmentManagementClientBundle\Http\ResponseHelper;
use OAT\Library\EnvironmentManagementClient\Http\AuthorizationDetailsMarkerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHelperTest extends TestCase
{
    public function testWithAuthorizationDetailsMarker(): void
    {
        $messageFactoryMock = $this->createMock(HttpMessageFactoryInterface::class);
        $foundationFactoryMock = $this->createMock(HttpFoundationFactoryInterface::class);
        $authMarkerFactoryMock = $this->createMock(AuthorizationDetailsMarkerInterface::class);

        $response = new Response();
        $response->headers->set('X-OAT-WITH-AUTH-DETAILS', json_encode([
            'clientId' => 'clientId',
            'refreshTokenId' => 'refreshTokenId',
            'userIdentifier' => null,
            'userRole' => null,
            'mode' => AuthorizationDetailsMarkerInterface::MODE_COOKIE,
            'url' => 'frontendUrl',
        ]));
        $psrResponse = $this->createMock(ResponseInterface::class);

        $messageFactoryMock->expects($this->once())
            ->method('createResponse')
            ->with($response)
            ->willReturn($psrResponse);

        $authMarkerFactoryMock->expects($this->once())
            ->method('withAuthDetails')
            ->with($psrResponse, "clientId", "refreshTokenId", null, null, "frontendUrl", AuthorizationDetailsMarkerInterface::MODE_COOKIE)
            ->willReturn($psrResponse);

        $foundationFactoryMock->expects($this->once())
            ->method('createResponse')
            ->with($psrResponse)
            ->willReturn($response);

        $subject = new ResponseHelper($messageFactoryMock, $foundationFactoryMock, $authMarkerFactoryMock);

        $response = $subject->withAuthorizationDetailsMarker($response, "clientId", "refreshTokenId", null, null, "frontendUrl");
        $withAuthDetailsHeader = $response->headers->get('X-OAT-WITH-AUTH-DETAILS');

        $this->assertNotNull(
            $withAuthDetailsHeader,
            "withAuthDetails is null"
        );

        $res_array = (array)json_decode($withAuthDetailsHeader);

        $this->assertArrayHasKey('clientId', $res_array);
        $this->assertEquals('clientId', $res_array['clientId']);
        $this->assertArrayHasKey('refreshTokenId', $res_array);
        $this->assertEquals('refreshTokenId', $res_array['refreshTokenId']);
        $this->assertEquals('frontendUrl', $res_array['url']);
        $this->assertArrayHasKey('mode', $res_array);
        $this->assertEquals(AuthorizationDetailsMarkerInterface::MODE_COOKIE, $res_array['mode']);
    }
}
