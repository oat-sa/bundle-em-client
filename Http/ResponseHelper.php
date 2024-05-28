<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http;

use OAT\Library\EnvironmentManagementClient\Http\AuthorizationDetailsMarkerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHelper
{
    private HttpMessageFactoryInterface $httpMessageFactory;
    private HttpFoundationFactoryInterface $httpFoundationFactory;
    private AuthorizationDetailsMarkerInterface $authorizationDetailsHeaderMarker;

    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        AuthorizationDetailsMarkerInterface $authorizationDetailsHeaderMarker
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->authorizationDetailsHeaderMarker = $authorizationDetailsHeaderMarker;
    }

    public function withAuthorizationDetailsMarker(
        Response $response,
        string $clientId,
        string $refreshTokenId,
        string $userIdentifier = null,
        string $userRole = null,
        string $cookieDomain = null,
        string $ltiToken = null,
        string $mode = AuthorizationDetailsMarkerInterface::MODE_COOKIE,
    ): Response {
        $psrResponse = $this->authorizationDetailsHeaderMarker->withAuthDetails(
            $this->httpMessageFactory->createResponse($response),
            $clientId,
            $refreshTokenId,
            $userIdentifier,
            $userRole,
            $cookieDomain,
            $ltiToken,
            $mode,
        );

        return $this->httpFoundationFactory->createResponse($psrResponse);
    }
}
