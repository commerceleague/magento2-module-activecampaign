<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface GuestCustomerRepositoryInterface
 */
interface GuestCustomerRepositoryInterface
{

    /**
     *
     * @throws CouldNotSaveException
     */
    public function save(Data\GuestCustomerInterface $customer): Data\GuestCustomerInterface;

    /**
     * @param int $entityId
     */
    public function getById($entityId): Data\GuestCustomerInterface;

    public function getByEmail(string $email): Data\GuestCustomerInterface;

    /**
     *
     * @throws CouldNotDeleteException
     */
    public function delete(Data\GuestCustomerInterface $customer): bool;

    /**
     * @param int $entityId
     *
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}