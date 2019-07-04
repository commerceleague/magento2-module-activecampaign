<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class SyncCustomerConsumer
 */
class SyncCustomerConsumer
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
     * @var ClientHelper
     */
    private $clientHelper;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logger $logger
     * @param ClientHelper $clientHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Logger $logger,
        ClientHelper $clientHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->clientHelper = $clientHelper;
    }

    /**
     * @param string $message
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        try {
            /** @var AbstractModel|CustomerInterface $customer */
            $customer = $this->customerRepository->getOrCreateByMagentoCustomerId($message['magento_customer_id']);
        } catch (CouldNotSaveException $e) {
            $this->logger->error(__('Unable to find customer with id "%1".', $message->getEntityId()));
            return;
        }

        try {
            $apiResponse = $this->performApiRequest($customer, $message['request']);
        } catch (HttpException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $customer->setActiveCampaignId($apiResponse['ecomCustomer']['id']);

        try {
            $this->customerRepository->save($customer);
        } catch (CouldNotSaveException $e) {
            $this->logger->error($e->getMessage());
        }
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
            return $this->clientHelper->getCustomerApi()->update((int)$activeCampaignId, ['ecomCustomer' => $request]);
        } else {
            return $this->clientHelper->getCustomerApi()->create(['ecomCustomer' => $request]);
        }
    }
}
