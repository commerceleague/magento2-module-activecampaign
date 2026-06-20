<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue;

use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\Model\Export\UnprocessableOutcome;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

class AbstractConsumerTest extends AbstractTestCase
{
    /**
     * @var MockObject|Logger
     */
    protected $logger;

    /**
     * @var AbstractConsumer
     */
    protected $consumer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);

        $this->consumer = new class ($this->logger) extends AbstractConsumer {
            /**
             * @param array<mixed> $request
             * @return array<string,mixed>
             */
            public function processDuplicateEntity(array $request, string $key): array
            {
                return [$key => ['id' => 99, 'request' => $request]];
            }
        };
    }

    private function invokeHandler(UnprocessableEntityHttpException $e, array $request, string $key): UnprocessableOutcome
    {
        $method = new ReflectionMethod(AbstractConsumer::class, 'handleUnprocessableEntityHttpException');
        $method->setAccessible(true);

        return $method->invoke($this->consumer, $e, $request, $key);
    }

    public function testEmptyErrorsReturnsUnknownWithoutTypeError()
    {
        /** @var MockObject|UnprocessableEntityHttpException $exception */
        $exception = $this->createMock(UnprocessableEntityHttpException::class);
        $exception->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn([]);

        $outcome = $this->invokeHandler($exception, ['request'], 'ecomOrder');

        $this->assertInstanceOf(UnprocessableOutcome::class, $outcome);
        $this->assertSame(UnprocessableOutcome::TYPE_UNKNOWN, $outcome->type);
        $this->assertFalse($outcome->isDuplicate());
        $this->assertSame([], $outcome->payload);
    }

    public function testDuplicateReturnsResolvedPayload()
    {
        /** @var MockObject|UnprocessableEntityHttpException $exception */
        $exception = $this->createMock(UnprocessableEntityHttpException::class);
        $exception->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn([['code' => 'duplicate']]);

        $outcome = $this->invokeHandler($exception, ['request'], 'ecomOrder');

        $this->assertSame(UnprocessableOutcome::TYPE_DUPLICATE, $outcome->type);
        $this->assertTrue($outcome->isDuplicate());
        $this->assertSame('duplicate', $outcome->code);
        $this->assertArrayHasKey('ecomOrder', $outcome->payload);
        $this->assertSame(99, $outcome->payload['ecomOrder']['id']);
    }

    public function testNonDuplicateReturnsValidationWithCodeAndMessage()
    {
        /** @var MockObject|UnprocessableEntityHttpException $exception */
        $exception = $this->createMock(UnprocessableEntityHttpException::class);
        $exception->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn([['code' => 'email_invalid', 'title' => 'Email is invalid']]);

        $outcome = $this->invokeHandler($exception, ['request'], 'ecomCustomer');

        $this->assertSame(UnprocessableOutcome::TYPE_VALIDATION, $outcome->type);
        $this->assertFalse($outcome->isDuplicate());
        $this->assertSame('email_invalid', $outcome->code);
        $this->assertSame('Email is invalid', $outcome->message);
        $this->assertSame([], $outcome->payload);
    }

    public function testLogFailureProducesSingleStructuredErrorLine()
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('order'),
                $this->stringContains('42'),
                $this->stringContains('123'),
                $this->stringContains('422'),
                $this->stringContains('duplicate'),
                $this->stringContains('Email is invalid')
            ));

        $method = new ReflectionMethod(AbstractConsumer::class, 'logFailure');
        $method->setAccessible(true);
        $method->invoke($this->consumer, 'order', 42, 123, 422, 'duplicate', 'Email is invalid');
    }

    public function testLogFailureRendersNullsWithoutFatal()
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('guest_customer'),
                $this->stringContains('null')
            ));

        $method = new ReflectionMethod(AbstractConsumer::class, 'logFailure');
        $method->setAccessible(true);
        $method->invoke($this->consumer, 'guest_customer', null, null, null, null, null);
    }
}
