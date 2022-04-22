<?php

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http\ArgumentValueResolver;

use Generator;
use OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver\LtiMessagePayloadValueResolver;
use OAT\Library\EnvironmentManagementClient\Http\LtiMessageExtractorInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayload;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class LtiMessagePayloadValueResolverTest extends TestCase
{
    private MockObject|HttpMessageFactoryInterface $messageFactoryMock;
    private MockObject|LtiMessageExtractorInterface $ltiMessageExtractorMock;
    private LtiMessagePayloadValueResolver $subject;

    protected function setUp(): void
    {
        $this->messageFactoryMock = $this->createMock(HttpMessageFactoryInterface::class);
        $this->ltiMessageExtractorMock = $this->createMock(LtiMessageExtractorInterface::class);

        $this->subject = new LtiMessagePayloadValueResolver(
            $this->messageFactoryMock,
            $this->ltiMessageExtractorMock,
        );
    }

    public function testSupports(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);

        $metadataMock->expects($this->once())
            ->method('getType')
            ->willReturn(LtiMessagePayloadInterface::class);

        $this->assertTrue($this->subject->supports(new Request(), $metadataMock));
    }

    public function testResolve(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);
        $psrRequest = $this->createMock(ServerRequestInterface::class);
        $ltiMessagePayloadMock = $this->createMock(LtiMessagePayload::class);
        $request = new Request();

        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')
            ->with($request)
            ->willReturn($psrRequest);

        $this->ltiMessageExtractorMock->expects($this->once())
            ->method('extract')
            ->with($psrRequest)
            ->willReturn($ltiMessagePayloadMock);

        $generator = $this->subject->resolve($request, $metadataMock);
        $this->assertInstanceOf(Generator::class, $generator);
        $this->assertEquals($ltiMessagePayloadMock, $generator->current());
    }
}