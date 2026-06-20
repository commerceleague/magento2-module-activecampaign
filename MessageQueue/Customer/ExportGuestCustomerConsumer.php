<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Api\GuestCustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Model\Export\DuplicateNotFoundException;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class ExportCustomerConsumer
 */
class ExportGuestCustomerConsumer extends AbstractConsumer implements ConsumerInterface
{

    public function __construct(
        Logger $logger,
        private readonly GuestCustomerRepositoryInterface $customerRepository,
        private readonly CustomerRequestBuilder $customerRequestBuilder,
        private readonly Client $client,
        private readonly FailureRecorder $failureRecorder
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        $customerData = $message['customer_data'];

        $guestCustomer = $this->customerRepository->getOrCreate($customerData);
        $request       = $this->customerRequestBuilder->buildWithGuest($guestCustomer);

        if (!$guestCustomer->getActiveCampaignId()) {
            try {
                $apiResponse = $this->performApiRequest($guestCustomer, $request);

                $activeCampaignId = $this->extractActiveCampaignId(
                    $apiResponse[self::RESPONSE_KEY_CUSTOMER]['id'] ?? null
                );
                if ($activeCampaignId === null) {
                    $this->getLogger()->error(sprintf(
                        '%s: missing "%s.id" in API response for guest customer id "%s"; skipping save.',
                        static::class,
                        self::RESPONSE_KEY_CUSTOMER,
                        (string)$guestCustomer->getId()
                    ));
                    $this->failureRecorder->recordFailure($guestCustomer, 'empty_response', null);
                    $this->customerRepository->save($guestCustomer);
                    return;
                }

                $guestCustomer->setActiveCampaignId($activeCampaignId);
                $this->failureRecorder->recordSuccess($guestCustomer);
                $this->customerRepository->save($guestCustomer);
            } catch (UnprocessableEntityHttpException $e) {
                try {
                    $outcome = $this->handleUnprocessableEntityHttpException(
                        $e,
                        $request,
                        self::RESPONSE_KEY_CUSTOMER
                    );
                } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                    $this->logUnprocessableEntityHttpException($duplicateLookupException, $request);
                    return;
                }

                $duplicateId = $outcome->isDuplicate()
                    ? $this->extractActiveCampaignId($outcome->payload[self::RESPONSE_KEY_CUSTOMER]['id'] ?? null)
                    : null;
                if ($duplicateId !== null) {
                    $guestCustomer->setActiveCampaignId($duplicateId);
                    $this->failureRecorder->recordSuccess($guestCustomer);
                    $this->customerRepository->save($guestCustomer);
                    return;
                }

                $this->logUnprocessableEntityHttpException($e, $request);
                $this->failureRecorder->recordFailure($guestCustomer, $outcome->code ?? 'unknown', $outcome->message);
                $this->customerRepository->save($guestCustomer);
                return;
            } catch (HttpException $e) {
                $this->logException($e);
                $this->failureRecorder->recordFailure($guestCustomer, 'http_error', $e->getMessage());
                $this->customerRepository->save($guestCustomer);
                return;
            }
        }
    }

    /**
     * @throws HttpException
     */
    private function performApiRequest(GuestCustomerInterface $customer, array $request): array
    {
        if ($activeCampaignId = $customer->getActiveCampaignId()) {
            return $this->client->getCustomerApi()->update((int)$activeCampaignId, ['ecomCustomer' => $request]);
        } else {
            return $this->client->getCustomerApi()->create(['ecomCustomer' => $request]);
        }
    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): array
    {
        $response = $this->client->getCustomerApi()->listPerPage(
            1,
            0,
            [
                'filters' => [
                    'email' => $request['email']
                ]
            ]
        );

        $items = $response->getItems();
        if ($items === []) {
            throw new DuplicateNotFoundException();
        }

        return [$key => $items[0]];
    }
}
