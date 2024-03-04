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
     * @throws CouldNotSaveException
     */
    public function save(Data\OrderInterface $order): Data\OrderInterface;

    /**
     * @param int $entityId
     */
    public function getById($entityId): Data\OrderInterface;

    /**
     * @param int $magentoQuoteId
     */
    public function getByMagentoQuoteId($magentoQuoteId): Data\OrderInterface;

    /**
     * @param int $magentoQuoteId
     * @throws CouldNotSaveException
     */
    public function getOrCreateByMagentoQuoteId($magentoQuoteId): Data\OrderInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(Data\OrderInterface $order): bool;

    /**
     * @param int $entityId
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
