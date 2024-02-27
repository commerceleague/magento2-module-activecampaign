<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Customer as CustomerResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class CustomerRepository
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        private readonly CustomerResource $customerResource,
        CustomerFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param Data\CustomerInterface|AbstractModel $customer
     * @return Data\CustomerInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\CustomerInterface $customer): Data\CustomerInterface
    {
        try {
            $this->customerResource->save($customer);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getById($entityId): Data\CustomerInterface
    {
        /** @var Customer $customer */
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $entityId);

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface
    {
        /** @var Customer $customer */
        $customer = $this->customerFactory->create();
        $this->customerResource->load(
            $customer,
            $magentoCustomerId,
            Data\CustomerInterface::MAGENTO_CUSTOMER_ID
        );

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface
    {
        $customer = $this->getByMagentoCustomerId($magentoCustomerId);

        if (!$customer->getId()) {
            $customer->setMagentoCustomerId($magentoCustomerId);
            $this->save($customer);
        }

        return $customer;
    }

    /**
     * @param Data\CustomerInterface|AbstractModel $customer
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CustomerInterface $customer): bool
    {
        try {
            $this->customerResource->delete($customer);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($entityId): bool
    {
        $customer = $this->getById($entityId);

        if (!$customer->getId()) {
            throw new NoSuchEntityException(
                __('The Customer with the "%1" ID doesn\'t exist', $entityId)
            );
        }

        return $this->delete($customer);
    }
}
