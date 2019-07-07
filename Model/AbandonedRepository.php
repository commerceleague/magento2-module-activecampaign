<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\AbandonedRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned as AbandonedResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class AbandonedRepository
 */
class AbandonedRepository implements AbandonedRepositoryInterface
{
    /**
     * @var AbandonedResource
     */
    private $abandonedResource;

    /**
     * @var AbandonedFactory
     */
    private $abandonedFactory;

    /**
     * @param AbandonedResource $abandonedResource
     * @param AbandonedFactory $abandonedFactory
     */
    public function __construct(
        AbandonedResource $abandonedResource,
        AbandonedFactory $abandonedFactory
    ) {
        $this->abandonedResource = $abandonedResource;
        $this->abandonedFactory = $abandonedFactory;
    }

    /**
     * @param Data\AbandonedInterface|AbstractModel $abandoned
     * @return Data\AbandonedInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\AbandonedInterface $abandoned): Data\AbandonedInterface
    {
        try {
            $this->abandonedResource->save($abandoned);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $abandoned;
    }

    /**
     * @inheritDoc
     */
    public function getById($entityId): Data\AbandonedInterface
    {
        /** @var Abandoned $abandoned */
        $abandoned = $this->abandonedFactory->create();
        $this->abandonedResource->load($abandoned, $entityId);

        return $abandoned;
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId($quoteId): Data\AbandonedInterface
    {
        /** @var Abandoned $abandoned */
        $abandoned = $this->abandonedFactory->create();
        $this->abandonedResource->load(
            $abandoned,
            $quoteId,
            Data\AbandonedInterface::QUOTE_ID
        );

        return $abandoned;
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateByQuoteId($quoteId): Data\AbandonedInterface
    {
        $abandoned = $this->getByQuoteId($quoteId);

        if (!$abandoned->getId()) {
            $abandoned->setQuoteId($quoteId);
            $this->save($abandoned);
        }

        return $abandoned;
    }

    /**
     * @param Data\AbandonedInterface|AbstractModel $abandoned
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\AbandonedInterface $abandoned): bool
    {
        try {
            $this->abandonedResource->delete($abandoned);
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
        $abandoned = $this->getById($entityId);

        if (!$abandoned->getId()) {
            throw new NoSuchEntityException(
                __('The Abandoned Cart with the "%1" ID doesn\'t exist', $entityId)
            );
        }

        return $this->delete($abandoned);
    }
}
