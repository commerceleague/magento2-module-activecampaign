<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Newsletter;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\TagContactBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;

/**
 * Class TagSubscriber
 *
 * @package CommerceLeague\ActiveCampaign\MessageQueue\Newsletter
 */
class TagSubscriber implements ConsumerInterface
{

    /**
     * @var TagContactBuilder
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
     * @param TagContactBuilder          $tagContactBuilder
     * @param Client                     $client
     * @param ContactRepositoryInterface $contactRepository
     */
    public function __construct(
        TagContactBuilder $tagContactBuilder,
        Client $client,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->requestBuilder    = $tagContactBuilder;
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

        $tagIds   = $message['tags'];
        $requests = $this->buildRequests($contact, $tagIds);

        foreach ($requests as $request) {
            try {
                $apiResponse = $this->client->getContactApi()->tagContact(['contactTag' => $request]);
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

    /**
     * @param ContactInterface $contact
     * @param array            $tagIds
     *
     * @return array
     */
    private function buildRequests(ContactInterface $contact, array $tagIds): array
    {
        $requestBuilder = $this->requestBuilder;
        return array_map(
            function ($tagId) use ($requestBuilder, $contact) {
                return $requestBuilder->buildWithContact($contact, $tagId);
            },
            $tagIds
        );
    }
}