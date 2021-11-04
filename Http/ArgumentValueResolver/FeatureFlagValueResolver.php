<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver;

use OAT\Library\EnvironmentManagementClient\Http\FeatureFlagExtractorInterface;
use OAT\Library\EnvironmentManagementClient\Model\FeatureFlagCollection;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class FeatureFlagValueResolver implements ArgumentValueResolverInterface
{
    private HttpMessageFactoryInterface $psrHttpFactory;
    private FeatureFlagExtractorInterface $featureFlagExtractor;

    public function __construct(
        HttpMessageFactoryInterface $psrHttpFactory,
        FeatureFlagExtractorInterface $featureFlagExtractor
    ) {
        $this->psrHttpFactory = $psrHttpFactory;
        $this->featureFlagExtractor = $featureFlagExtractor;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return FeatureFlagCollection::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        yield $this->featureFlagExtractor->extract($psrRequest);
    }
}
