<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver;

use Lcobucci\JWT\Token;
use OAT\Library\EnvironmentManagementClient\Exception\TokenUnauthorizedException;
use OAT\Library\EnvironmentManagementClient\Http\JWTTokenExtractorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AccessTokenValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private HttpMessageFactoryInterface $httpMessageFactory,
        private JWTTokenExtractorInterface $jwtTokenExtractor
    ) {}

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Token::class === $argument->getType()
            && 'accessToken' === $argument->getName();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $psrRequest = $this->httpMessageFactory->createRequest($request);

        try {
            yield $this->jwtTokenExtractor->extract($psrRequest);
        } catch (TokenUnauthorizedException $exception) {
            throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
        }
    }
}
