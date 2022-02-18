<?php

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver;

use OAT\Library\EnvironmentManagementClient\Http\LtiMessageExtractorInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class LtiMessagePayloadValueResolver implements ArgumentValueResolverInterface
{
    private HttpMessageFactoryInterface $httpMessageFactory;
    private LtiMessageExtractorInterface $ltiMessageExtractor;

    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        LtiMessageExtractorInterface $ltiMessageExtractor
    ) {
        $this->httpMessageFactory = $httpMessageFactory;
        $this->ltiMessageExtractor = $ltiMessageExtractor;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === LtiMessagePayloadInterface::class;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $psrRequest = $this->httpMessageFactory->createRequest($request);

        yield $this->ltiMessageExtractor->extract($psrRequest);
    }
}
