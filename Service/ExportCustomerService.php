<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Service;

use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class ExportCustomerService
 */
class ExportCustomerService
{

    /**
     * @var CustomerRequestBuilder
     */
    private $customerRequestBuilder;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param CustomerRequestBuilder $customerRequestBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        CustomerRequestBuilder $customerRequestBuilder,
        PublisherInterface $publisher
    ) {
        $this->customerRequestBuilder = $customerRequestBuilder;
        $this->publisher = $publisher;
    }

    /**
     * @param MagentoCustomer $magentoCustomer
     */
    public function export(MagentoCustomer $magentoCustomer): void
    {
        $data = [
            'magento_customer_id' => $magentoCustomer->getId(),
            'request' => $this->customerRequestBuilder->build($magentoCustomer)
        ];

        $this->publisher->publish(Topics::CUSTOMER_EXPORT, json_encode($data));
    }
}
