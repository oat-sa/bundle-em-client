<?php

declare(strict_types=1);

namespace OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver;

use OAT\Library\EnvironmentManagementClient\Exception\TenantIdNotFoundException;
use OAT\Library\EnvironmentManagementClient\Http\TenantIdExtractorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class TenantIdValueResolver implements ArgumentValueResolverInterface
{
    private HttpMessageFactoryInterface $psrHttpFactory;
    private TenantIdExtractorInterface $tenantIdExtractor;

    public function __construct(
        HttpMessageFactoryInterface $psrHttpFactory,
        TenantIdExtractorInterface $tenantIdExtractor
    ) {
        $this->psrHttpFactory = $psrHttpFactory;
        $this->tenantIdExtractor = $tenantIdExtractor;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === 'string' && $argument->getName() === 'tenantId';
    }

    /**
     * @throws TenantIdNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        yield $this->tenantIdExtractor->extract($psrRequest);
    }
}
