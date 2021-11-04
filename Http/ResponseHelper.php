<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http;

use OAT\Library\EnvironmentManagementClient\Http\AuthorizationDetailsMarkerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHelper
{
    private HttpMessageFactoryInterface $psrHttpFactory;
    private HttpFoundationFactoryInterface $httpFoundationFactory;
    private AuthorizationDetailsMarkerInterface $authorizationDetailsHeaderMarker;

    public function __construct(
        HttpMessageFactoryInterface $psrHttpFactory,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        AuthorizationDetailsMarkerInterface $authorizationDetailsHeaderMarker
    ) {
        $this->psrHttpFactory = $psrHttpFactory;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->authorizationDetailsHeaderMarker = $authorizationDetailsHeaderMarker;
    }

    public function withAuthorizationDetailsMarker(Response $response): Response
    {
        $psrResponse = $this->psrHttpFactory->createResponse($response);
        $psrResponse = $this->authorizationDetailsHeaderMarker->withAuthDetails($psrResponse);

        return $this->httpFoundationFactory->createResponse($psrResponse);
    }
}
