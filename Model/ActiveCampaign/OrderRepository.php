<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\Order as OrderResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @param OrderResource $orderResource
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderResource $orderResource,
        OrderFactory $orderFactory
    ) {
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param Data\OrderInterface|AbstractModel $order
     * @return Data\OrderInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\OrderInterface $order): Data\OrderInterface
    {
        try {
            $this->orderResource->save($order);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function getById($entityId): Data\OrderInterface
    {
        /** @var Order $order */
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $entityId);

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function getByMagentoQuoteId($magentoQuoteId): Data\OrderInterface
    {
        /** @var Order $order */
        $order = $this->orderFactory->create();
        $this->orderResource->load(
            $order,
            $magentoQuoteId,
            Data\OrderInterface::MAGENTO_QUOTE_ID
        );

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateByMagentoQuoteId($magentoQuoteId): Data\OrderInterface
    {
        $order = $this->getByMagentoQuoteId($magentoQuoteId);

        if (!$order->getId()) {
            $order->setMagentoQuoteId($magentoQuoteId);
            $this->save($order);
        }

        return $order;
    }

    /**
     * @param Data\OrderInterface|AbstractModel $order
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\OrderInterface $order): bool
    {
        try {
            $this->orderResource->delete($order);
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
        $order = $this->getById($entityId);

        if (!$order->getId()) {
            throw new NoSuchEntityException(
                __('The Order with the "%1" ID doesn\'t exist', $entityId)
            );
        }

        return $this->delete($order);
    }
}
