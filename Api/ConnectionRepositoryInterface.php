<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface ConnectionRepositoryInterface
 */
interface ConnectionRepositoryInterface
{
    /**
     * @param Data\ConnectionInterface $connection
     * @return Data\ConnectionInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\ConnectionInterface $connection): Data\ConnectionInterface;

    /**
     * @param int $connectionId
     * @return Data\ConnectionInterface
     * @throws NoSuchEntityException
     */
    public function getById($connectionId): Data\ConnectionInterface;

    /**
     * @param Data\ConnectionInterface $connection
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ConnectionInterface $connection): bool;

    /**
     * @param int $connectionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($connectionId): bool;
}
