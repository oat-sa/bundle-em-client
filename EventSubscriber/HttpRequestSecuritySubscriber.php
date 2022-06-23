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

namespace OAT\Bundle\EnvironmentManagementClientBundle\EventSubscriber;

use OAT\Library\EnvironmentManagementClient\Exception\TokenUnauthorizedException;
use OAT\Library\EnvironmentManagementClient\Http\JWTTokenExtractorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpRequestSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JWTTokenExtractorInterface $jwtTokenExtractor,
        private HttpMessageFactoryInterface $httpMessageFactory,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['validateRouteOauth2Scopes', 10],
            ],
        ];
    }

    public function validateRouteOauth2Scopes(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $allowedScopes = $request->attributes->get('em_oauth2_scopes');

        if (!$allowedScopes) {
            return;
        }

        $psrRequest = $this->httpMessageFactory->createRequest($request);

        try {
            $token = $this->jwtTokenExtractor->extract($psrRequest);
        } catch (TokenUnauthorizedException $exception) {
            throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
        }

        $scopes = $token->claims()->get('scopes');

        if (count(array_intersect($scopes, $allowedScopes)) === 0) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid scope(s)');
        }
    }
}
