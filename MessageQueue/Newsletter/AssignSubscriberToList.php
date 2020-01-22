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
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;

/**
 * Class AssignSubscriberToList
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue\Newsletter
 */
class AssignSubscriberToList implements ConsumerInterface
{

    /**
     * @var ContactListBuilder
     */
    private $requestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * AssignContactToListConsumer constructor.
     *
     * @param ContactListBuilder         $contactListBuilder
     * @param Client                     $client
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        ContactListBuilder $contactListBuilder,
        Client $client,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->requestBuilder    = $contactListBuilder;
        $this->client            = $client;
        $this->contactRepository = $contactRepository;
    }

    /**
     * @param string $message
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        $contact = $this->contactRepository->getById($message['contact_id']);
        $request = $this->requestBuilder->buildWithContact($contact, $message['list_id']);

        try {
            $apiResponse = $this->client->getContactApi()->updateListStatus(['contactList' => $request]);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error(print_r($e->getResponseErrors(), true));
            return;
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }
    }
}