<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaign\Model\Export\BackoffState;
use CommerceLeague\ActiveCampaign\Model\Export\DuplicateNotFoundException;
use CommerceLeague\ActiveCampaign\Model\Export\FailureRecorder;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ExportCustomerConsumer
 */
class ExportCustomerConsumer extends AbstractConsumer implements ConsumerInterface
{

    public function __construct(
        private readonly MagentoCustomerRepositoryInterface $magentoCustomerRepository,
        Logger $logger,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerRequestBuilder $customerRequestBuilder,
        private readonly Client $client,
        private readonly FailureRecorder $failureRecorder,
        private readonly BackoffState $backoffState
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if ($this->backoffState->shouldHalt()) {
            $this->getLogger()->warning('ActiveCampaign export backing off after repeated 503s; skipping');
            return;
        }

        try {
            $magentoCustomer = $this->magentoCustomerRepository->getById($message['magento_customer_id']);
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->getLogger()->error($e);
            return;
        }

        $customer = $this->customerRepository->getOrCreateByMagentoCustomerId($magentoCustomer->getId());
        $request  = $this->customerRequestBuilder->build($magentoCustomer);

        try {
            $apiResponse = $this->performApiRequest($customer, $request);

            $activeCampaignEcomCustomerId = $this->extractActiveCampaignId(
                $apiResponse[self::RESPONSE_KEY_CUSTOMER]['id'] ?? null
            );
            if ($activeCampaignEcomCustomerId === null) {
                $this->getLogger()->error(sprintf(
                    '%s: missing "%s.id" in API response for Magento customer id "%s"; skipping save.',
                    static::class,
                    self::RESPONSE_KEY_CUSTOMER,
                    $message['magento_customer_id']
                ));
                $this->failureRecorder->recordFailure($customer, 'empty_response', null);
                $this->customerRepository->save($customer);
                return;
            }

            $customer->setActiveCampaignId($activeCampaignEcomCustomerId);
            $this->backoffState->reset();
            $this->failureRecorder->recordSuccess($customer);
            $this->customerRepository->save($customer);
        } catch (UnprocessableEntityHttpException $e) {
            try {
                $outcome = $this->handleUnprocessableEntityHttpException($e, $request, self::RESPONSE_KEY_CUSTOMER);
            } catch (UnprocessableEntityHttpException $duplicateLookupException) {
                $this->logUnprocessableEntityHttpException($duplicateLookupException, $request);
                return;
            }

            $duplicateId = $outcome->isDuplicate()
                ? $this->extractActiveCampaignId($outcome->payload[self::RESPONSE_KEY_CUSTOMER]['id'] ?? null)
                : null;
            if ($duplicateId !== null) {
                $customer->setActiveCampaignId($duplicateId);
                $this->backoffState->reset();
                $this->failureRecorder->recordSuccess($customer);
                $this->customerRepository->save($customer);
                return;
            }

            $this->logUnprocessableEntityHttpException($e, $request);
            $this->failureRecorder->recordFailure($customer, $outcome->code ?? 'unknown', $outcome->message);
            $this->customerRepository->save($customer);
            return;
        } catch (HttpException $e) {
            if ($e->getCode() === 503) {
                $this->backoffState->record503();
            }
            $this->logException($e);
            $transient = $e->getCode() >= 500;
            $this->failureRecorder->recordFailure($customer, 'http_error', $e->getMessage(), $transient);
            $this->customerRepository->save($customer);
            return;
        }
    }

    /**
     * @throws HttpException
     */
    private function performApiRequest(CustomerInterface $customer, array $request): array
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
                    'email'        => $request['email'],
                    'connectionid' => $request['connectionid']
                ]
            ]
        );

        $items = $response->getItems();
        if ($items === []) {
            throw new DuplicateNotFoundException();
        }

        $item = $items[0];
        if (strtolower((string)$item['email']) !== strtolower((string)$request['email'])) {
            throw new DuplicateNotFoundException();
        }

        return [$key => $item];
    }
}
