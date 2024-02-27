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
     */
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * @param                                  $request
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException,
        $request
    ) {
        $this->getLogger()->error(get_class($this));
        $this->getLogger()->error($unprocessableEntityHttpException->getMessage());
        $this->getLogger()->error(print_r($unprocessableEntityHttpException->getResponseErrors(), true));
        $this->getLogger()->error(print_r($request, true));
    }

    public function logException(Exception $exception)
    {
        $this->getLogger()->error($exception);
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
