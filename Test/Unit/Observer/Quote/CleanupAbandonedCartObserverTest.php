<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Observer\Quote;

use CommerceLeague\ActiveCampaign\Api\Data\OrderInterface;
use CommerceLeague\ActiveCampaign\Api\OrderRepositoryInterface;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Observer\Quote\CleanupAbandonedCartObserver;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;

class CleanupAbandonedCartObserverTest extends AbstractTestCase
{

    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|Observer
     */
    protected $observer;

    /**
     * @var MockObject|Event
     */
    protected $event;

    /**
     * @var MockObject|Quote
     */
    protected $quote;

    /**
     * @var MockObject|OrderInterface
     */
    protected $acOrder;

    /**
     * @var CleanupAbandonedCartObserver
     */
    protected $cleanupAbandonedCartObserver;

    protected function setUp(): void
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->observer = $this->createMock(Observer::class);
        $this->event = $this->createMock(Event::class);
        $this->quote = $this->createMock(Quote::class);
        $this->acOrder = $this->createMock(OrderInterface::class);

        $this->cleanupAbandonedCartObserver = new CleanupAbandonedCartObserver(
            $this->configHelper,
            $this->orderRepository,
            $this->publisher
        );
    }

    public function testExecuteApiDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    public function testExecuteAbandonedCartExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(false);

        $this->observer->expects($this->never())
            ->method('getEvent');

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    public function testExecuteNoAcRowIsNoop()
    {
        $quoteId = 123;

        $this->enabled();
        $this->withQuoteId($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getByMagentoQuoteId')
            ->with($quoteId)
            ->willThrowException(new NoSuchEntityException());

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->orderRepository->expects($this->never())
            ->method('deleteById');

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    public function testExecutePureAbandonedCartPublishesDelete()
    {
        $quoteId = 123;
        $activeCampaignId = 789;
        $entityId = 42;

        $this->enabled();
        $this->withQuoteId($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->acOrder);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getMagentoOrderId')
            ->willReturn(null);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($entityId);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::QUOTE_DELETE_ABANDONED_CART,
                json_encode(['activecampaign_id' => $activeCampaignId, 'entity_id' => $entityId])
            );

        $this->orderRepository->expects($this->never())
            ->method('deleteById');

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    public function testExecuteConvertedDeletesLocalRowNoPublish()
    {
        $quoteId = 123;
        $activeCampaignId = 789;
        $entityId = 42;

        $this->enabled();
        $this->withQuoteId($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->acOrder);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn($activeCampaignId);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getMagentoOrderId')
            ->willReturn(555);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($entityId);

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->orderRepository->expects($this->once())
            ->method('deleteById')
            ->with($entityId)
            ->willReturn(true);

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    public function testExecuteNoAcIdDoesNotPublish()
    {
        $quoteId = 123;
        $entityId = 42;

        $this->enabled();
        $this->withQuoteId($quoteId);

        $this->orderRepository->expects($this->once())
            ->method('getByMagentoQuoteId')
            ->with($quoteId)
            ->willReturn($this->acOrder);

        $this->acOrder->expects($this->atLeastOnce())
            ->method('getActiveCampaignId')
            ->willReturn(null);

        $this->acOrder->expects($this->any())
            ->method('getId')
            ->willReturn($entityId);

        $this->publisher->expects($this->never())
            ->method('publish');

        $this->cleanupAbandonedCartObserver->execute($this->observer);
    }

    private function enabled(): void
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isAbandonedCartExportEnabled')
            ->willReturn(true);
    }

    private function withQuoteId(int $quoteId): void
    {
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->once())
            ->method('getData')
            ->with('quote')
            ->willReturn($this->quote);

        $this->quote->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($quoteId);
    }
}
