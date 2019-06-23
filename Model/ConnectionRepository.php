<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\ConnectionRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection as ConnectionResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class ConnectionRepository
 */
class ConnectionRepository implements ConnectionRepositoryInterface
{
    /**
     * @var ConnectionResource
     */
    private $connectionResource;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param ConnectionResource $connectionResource
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        ConnectionResource $connectionResource,
        ConnectionFactory $connectionFactory
    ) {
        $this->connectionResource = $connectionResource;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param Data\ConnectionInterface|AbstractModel $connection
     * @return Data\ConnectionInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\ConnectionInterface $connection): Data\ConnectionInterface
    {
        try {
            $this->connectionResource->save($connection);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $connection;
    }

    /**
     * @inheritDoc
     */
    public function getById($connectionId): Data\ConnectionInterface
    {
        /** @var Connection $connection */
        $connection = $this->connectionFactory->create();
        $this->connectionResource->load($connection, $connectionId);
        if (!$connection->getId()) {
            throw new NoSuchEntityException(__('The connection with the "%1" ID doesn\'t exist.', $connectionId));
        }

        return $connection;
    }

    /**
     * @param Data\ConnectionInterface|AbstractModel $connection
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ConnectionInterface $connection): bool
    {
        try {
            $this->connectionResource->delete($connection);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($connectionId): bool
    {
        return $this->delete($this->getById($connectionId));
    }
}
