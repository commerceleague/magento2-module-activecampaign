<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactListBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;

/**
 * Class AssignContactToListConsumer
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue\Customer
 */
class AssignContactToListConsumer implements ConsumerInterface
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
     * AssignContactToListConsumer constructor.
     *
     * @param ContactListBuilder $contactListBuilder
     * @param Client             $client
     */
    public function __construct(ContactListBuilder $contactListBuilder, Client $client)
    {
        $this->requestBuilder = $contactListBuilder;
        $this->client         = $client;
    }

    /**
     * @param string $message
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        $request = $this->requestBuilder->buildWithContact($message['contact_id'], $message['list_id']);

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