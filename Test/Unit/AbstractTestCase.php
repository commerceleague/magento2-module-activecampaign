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

//        $unprocessableEntityHttpException->expects($this->once())
//            ->method('getMessage')
//            ->willReturn($responseMessage);

//        $logger->expects($this->at(0))
//            ->method('error')
//            ->with(print_r($responseErrors, true));

//        $logger->expects($this->at(1))
//            ->method('error')
//            ->with($responseMessage);

        // PHPUnit 11 removed the ->at() matcher; assert the ordered error() calls
        // via a call-index counter while preserving the exactly(4) expectation and
        // the original argument assertions for the 3rd and 4th calls.
        $errorCallIndex = 0;
        $logger->expects($this->exactly(4))
            ->method('error')
            ->with($this->callback(function ($argument) use (&$errorCallIndex, $responseErrors, $request) {
                if ($errorCallIndex === 2) {
                    $this->assertSame(print_r($responseErrors, true), $argument);
                }

                if ($errorCallIndex === 3) {
                    $this->assertSame(print_r($request, true), $argument);
                }

                $errorCallIndex++;

                return true;
            }));
    }
}
