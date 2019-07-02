<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Client as ClientHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CreateConsumer
 */
class CreateConsumer
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
     * @param CreateMessage $message
     */
    public function consume(CreateMessage $message): void
    {
        $customer = $this->customerRepository->getById($message->getEntityId());

        if (!$customer->getId()) {
            $this->logger->error(__('Unable to find customer with id "%1".', $message->getEntityId()));
            return;
        }

        $request = json_decode($message->getSerializedRequest(), true);

        try {
            $apiResponse = $this->clientHelper->getCustomerApi()->create(['ecomCustomer' => $request]);
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
}
