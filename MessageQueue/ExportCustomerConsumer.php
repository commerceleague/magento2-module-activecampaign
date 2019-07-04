<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class ExportCustomerConsumer
 */
class ExportCustomerConsumer
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logger $logger
     * @param Client $client
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Logger $logger,
        Client $client
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);
        $customer = $this->customerRepository->getOrCreateByMagentoCustomerId($message['magento_customer_id']);

        try {
            $apiResponse = $this->performApiRequest($customer, $message['request']);
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $customer->setActiveCampaignId($apiResponse['ecomCustomer']['id']);

        $this->customerRepository->save($customer);
    }

    /**
     * @param CustomerInterface $customer
     * @param array $request
     * @return array
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
}
