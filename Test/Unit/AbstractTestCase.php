<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit;

use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase
 *
 * @package CommerceLeague\ActiveCampaign\Test\Unit
 */
abstract class AbstractTestCase extends TestCase
{

    public function unprocessableEntityHttpException(
        MockObject $apiEndpoint,
        MockObject $logger,
        array      $request,
        array      $responseErrors,
        string     $apiResponseKey,
        string     $apiMethod
    ) {
        /** @var MockObject|UnprocessableEntityHttpException $unprocessableEntityHttpException */
        $unprocessableEntityHttpException = $this->createMock(UnprocessableEntityHttpException::class);

        $apiEndpoint->expects($this->once())
            ->method($apiMethod)
            ->with([$apiResponseKey => $request])
            ->willThrowException($unprocessableEntityHttpException);

        $unprocessableEntityHttpException->expects($this->atLeastOnce())
            ->method('getResponseErrors')
            ->willReturn($responseErrors);

        // Task 6.1: failure logging is now a single structured line via logFailure()
        // instead of the old four-call print_r dump. Assert exactly one error() call
        // carrying the diagnosable "export failed [...] code=..." marker.
        $logger->expects($this->once())
            ->method('error')
            ->with($this->logicalAnd(
                $this->stringContains('export failed'),
                $this->stringContains('entity='),
                $this->stringContains('local_id='),
                $this->stringContains('http_status='),
                $this->stringContains('code=')
            ));
    }
}
