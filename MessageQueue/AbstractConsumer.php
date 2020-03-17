<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\Customer\ExportGuestCustomerConsumer;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Exception;

/**
 * Class AbstractConsumer
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue
 */
abstract class AbstractConsumer
{

    const RESPONSE_KEY_CUSTOMER = 'ecomCustomer';
    const RESPONSE_KEY_ORDER    = 'ecomOrder';
    const RESPONSE_KEY_CONTACT  = 'contact';

    const ERROR_CODE_DUPLICATE = 'duplicate';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AbstractConsumer constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param UnprocessableEntityHttpException $unprocessableEntityHttpException
     * @param                                  $request
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        $request
    ) {
        $this->logger->error(__CLASS__);
        $this->logger->error($unprocessableEntityHttpException->getMessage());
        $this->logger->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->logger->error(print_r($request, true));
    }

    /**
     * @param Exception $exception
     */
    public function logException(Exception $exception)
    {
        $this->logger->error($exception);
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     *
     * @param array  $request
     * @param string $key
     *
     * @return mixed
     */
    abstract function processDuplicateEntity(array $request, string $key);

    /**
     *
     * @param UnprocessableEntityHttpException $e
     * @param array                            $request
     * @param string                           $key
     *
     * @return array
     */
    protected function handleUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $e,
        array $request,
        string $key
    ) {
        $errors    = $e->getResponseErrors();
        $errors    = array_shift($errors);
        $errorCode = $errors['code'];

        switch (true) {
            case ($errorCode == self::ERROR_CODE_DUPLICATE):
                return $this->processDuplicateEntity($request, $key);
                break;
        }
    }
}