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
     * @throws CouldNotSaveException
     */
    public function save(Data\CustomerInterface $customer): Data\CustomerInterface;

    /**
     * @param int $entityId
     */
    public function getById($entityId): Data\CustomerInterface;

    /**
     * @param int $magentoCustomerId
     */
    public function getByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface;

    /**
     * @param int $magentoCustomerId
     * @throws CouldNotSaveException
     */
    public function getOrCreateByMagentoCustomerId($magentoCustomerId): Data\CustomerInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CustomerInterface $customer): bool;

    /**
     * @param int $entityId
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
