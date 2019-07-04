<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface CustomerRepositoryInterface
 */
interface CustomerRepositoryInterface
{
    /**
     * @param Data\CustomerInterface $customer
     * @return Data\CustomerInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\CustomerInterface $customer): Data\CustomerInterface;

    /**
     * @param int $entityId
     * @return Data\CustomerInterface
     */
    public function getById($entityId): Data\CustomerInterface;

    /**
     * @param int $magentoCustomerId
     * @return Data\CustomerInterface
     */
    public function getByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface;

    /**
     * @param int $magentoCustomerId
     * @return Data\CustomerInterface
     * @throws CouldNotSaveException
     */
    public function getOrCreateByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface;

    /**
     * @param Data\CustomerInterface $customer
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CustomerInterface $customer): bool;

    /**
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
