<?php

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver;

use OAT\Library\EnvironmentManagementClient\Http\RegistrationIdExtractorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RegistrationIdValueResolver implements ArgumentValueResolverInterface
{
    private HttpMessageFactoryInterface $httpMessageFactory;
    private RegistrationIdExtractorInterface $registrationIdExtractor;

    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        RegistrationIdExtractorInterface $registrationIdExtractor
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->registrationIdExtractor = $registrationIdExtractor;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === 'string' && $argument->getName() === 'registrationId';
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $psrRequest = $this->httpMessageFactory->createRequest($request);

        yield $this->registrationIdExtractor->extract($psrRequest);
    }
}
