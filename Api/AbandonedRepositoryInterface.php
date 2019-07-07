<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface AbandonedRepositoryInterface
 */
interface AbandonedRepositoryInterface
{
    /**
     * @param Data\AbandonedInterface $abandoned
     * @return Data\AbandonedInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\AbandonedInterface $abandoned): Data\AbandonedInterface;

    /**
     * @param int $entityId
     * @return Data\AbandonedInterface
     */
    public function getById($entityId): Data\AbandonedInterface;

    /**
     * @param int $quoteId
     * @return Data\AbandonedInterface
     */
    public function getByQuoteId($quoteId): Data\AbandonedInterface;

    /**
     * @param int $quoteId
     * @return Data\AbandonedInterface
     * @throws CouldNotSaveException
     */
    public function getOrCreateByQuoteId($quoteId): Data\AbandonedInterface;

    /**
     * @param Data\AbandonedInterface $abandoned
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\AbandonedInterface $abandoned): bool;

    /**
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
