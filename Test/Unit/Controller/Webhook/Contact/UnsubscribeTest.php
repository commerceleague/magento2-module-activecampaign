<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Controller\Webhook\Contact;

use CommerceLeague\ActiveCampaign\Controller\Webhook\Contact\Unsubscribe;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use PHPUnit\Framework\MockObject\MockObject;

class UnsubscribeTest extends AbstractTestCase
{
    /**
     * @var MockObject|RequestInterface
     */
    protected $request;

    /**
     * @var MockObject|SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var Unsubscribe
     */
    protected $unsubscribe;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);

        $this->subscriber = $this->createMock(Subscriber::class);
        $this->subscriberFactory = $this->createMock(SubscriberFactory::class);
        $this->subscriberFactory->method('create')->willReturn($this->subscriber);

        $this->unsubscribe = new Unsubscribe(
            $context,
            $this->createMock(ConfigHelper::class),
            $this->createMock(RawResultFactory::class),
            $this->subscriberFactory,
            $this->createMock(Logger::class)
        );
    }

    /**
     * The regression this guards. loadByEmail() on an address that is not a subscriber leaves an empty
     * model whose getId() returns NULL. The guard used to read `=== 0`, which never matched, so execution
     * fell through to unsubscribe() on an empty subscriber; sendUnsubscriptionEmail() then called
     * addTo(null, null) and Magento's mail layer raised a TypeError. Because ActiveCampaign holds guests
     * and imported contacts that were never Magento newsletter subscribers, most webhook calls 500'd —
     * and ActiveCampaign disables a webhook that keeps failing.
     */
    public function testDoesNotUnsubscribeAnUnknownEmailAddress(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['contact' => ['email' => 'nobody@example.com']]);

        $this->subscriber->expects($this->once())
            ->method('loadByEmail')
            ->with('nobody@example.com');

        $this->subscriber->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->subscriber->expects($this->never())
            ->method('unsubscribe');

        $this->unsubscribe->execute();
    }

    public function testUnsubscribesAKnownEmailAddress(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['contact' => ['email' => 'shopper@example.com']]);

        $this->subscriber->expects($this->once())->method('loadByEmail')->with('shopper@example.com');
        $this->subscriber->expects($this->once())->method('getId')->willReturn(4711);
        $this->subscriber->expects($this->once())->method('unsubscribe');

        $this->unsubscribe->execute();
    }

    public function testIgnoresAPayloadWithoutAContactEmail(): void
    {
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn(['contact' => []]);

        $this->subscriberFactory->expects($this->never())->method('create');

        $this->unsubscribe->execute();
    }
}
