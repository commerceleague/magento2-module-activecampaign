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
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ExportCustomerConsumer
 */
class ExportCustomerConsumer implements ConsumerInterface
{
    /**
     * @var MagentoCustomerRepositoryInterface
     */
    private $magentoCustomerRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerRequestBuilder
     */
    private $customerRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param MagentoCustomerRepositoryInterface $magentoCustomerRepository
     * @param Logger $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerRequestBuilder $customerRequestBuilder
     * @param Client $client
     */
    public function __construct(
        MagentoCustomerRepositoryInterface $magentoCustomerRepository,
        Logger $logger,
        CustomerRepositoryInterface $customerRepository,
        CustomerRequestBuilder $customerRequestBuilder,
        Client $client
    ) {
        $this->magentoCustomerRepository = $magentoCustomerRepository;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->customerRequestBuilder = $customerRequestBuilder;
        $this->client = $client;
    }

    /**
     * @param string $message
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        try {
            $magentoCustomer = $this->magentoCustomerRepository->getById($message['magento_customer_id']);
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $customer = $this->customerRepository->getOrCreateByMagentoCustomerId($magentoCustomer->getId());
        $request = $this->customerRequestBuilder->build($magentoCustomer);

        try {
            $apiResponse = $this->performApiRequest($customer, $request);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error(print_r($e->getResponseErrors(), true));
            return;
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
