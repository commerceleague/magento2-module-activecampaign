<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactListBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;

/**
 * Class AssignSubscriberToList
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue\Newsletter
 */
class AssignSubscriberToList extends AbstractConsumer implements ConsumerInterface
{

    /**
     * AssignContactToListConsumer constructor.
     */
    public function __construct(
        private readonly ContactListBuilder $requestBuilder,
        private readonly Client $client,
        private readonly ContactRepositoryInterface $contactRepository,
        Logger $logger
    ) {
        parent::__construct($logger);
    }

    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        $contact = $this->contactRepository->getById($message['contact_id']);
        $request = $this->requestBuilder->buildWithContact($contact, $message['list_id']);

        try {
            $apiResponse = $this->client->getContactApi()->updateListStatus(['contactList' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }
    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): void
    {
    }
}