<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface OrderRepositoryInterface
 */
interface OrderRepositoryInterface
{
    /**
     * @return Data\OrderInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\OrderInterface $order): Data\OrderInterface;

    /**
     * @param int $entityId
     * @return Data\OrderInterface
     */
    public function getById($entityId): Data\OrderInterface;

    /**
     * @param int $magentoQuoteId
     * @return Data\OrderInterface
     */
    public function getByMagentoQuoteId($magentoQuoteId): Data\OrderInterface;

    /**
     * @param int $magentoQuoteId
     * @return Data\OrderInterface
     * @throws CouldNotSaveException
     */
    public function getOrCreateByMagentoQuoteId($magentoQuoteId): Data\OrderInterface;

    /**
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\OrderInterface $order): bool;

    /**
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
