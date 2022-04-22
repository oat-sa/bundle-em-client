<?php

namespace OAT\Bundle\EnvironmentManagementClientBundle\Tests\Unit\Http\ArgumentValueResolver;

use Generator;
use OAT\Bundle\EnvironmentManagementClientBundle\Http\ArgumentValueResolver\RegistrationIdValueResolver;
use OAT\Library\EnvironmentManagementClient\Http\RegistrationIdExtractorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RegistrationIdValueResolverTest extends TestCase
{
    private MockObject|HttpMessageFactoryInterface $messageFactoryMock;
    private MockObject|RegistrationIdExtractorInterface $registrationIdExtractorMock;
    private RegistrationIdValueResolver $subject;

    protected function setUp(): void
    {
        $this->messageFactoryMock = $this->createMock(HttpMessageFactoryInterface::class);
        $this->registrationIdExtractorMock = $this->createMock(RegistrationIdExtractorInterface::class);

        $this->subject = new RegistrationIdValueResolver(
            $this->messageFactoryMock,
            $this->registrationIdExtractorMock,
        );
    }

    public function testSupports(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);

        $metadataMock->expects($this->once())
            ->method('getType')
            ->willReturn('string');

        $metadataMock->expects($this->once())
            ->method('getName')
            ->willReturn('registrationId');

        $this->assertTrue($this->subject->supports(new Request(), $metadataMock));
    }

    public function testResolve(): void
    {
        $metadataMock = $this->createMock(ArgumentMetadata::class);
        $psrRequest = $this->createMock(ServerRequestInterface::class);
        $request = new Request();

        $this->messageFactoryMock->expects($this->once())
            ->method('createRequest')
            ->with($request)
            ->willReturn($psrRequest);

        $this->registrationIdExtractorMock->expects($this->once())
            ->method('extract')
            ->with($psrRequest)
            ->willReturn('registration-id');

        $generator = $this->subject->resolve($request, $metadataMock);
        $this->assertInstanceOf(Generator::class, $generator);
        $this->assertEquals('registration-id', $generator->current());
    }
}