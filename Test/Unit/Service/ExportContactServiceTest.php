<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Service;

use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Service\ExportContactService;
use Magento\Customer\Model\Customer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder as ContactRequestBuilder;

class ExportContactServiceTest extends TestCase
{
    /**
     * @var MockObject|ContactRequestBuilder
     */
    protected $contactRequestBuilder;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var ExportContactService
     */
    protected $exportContactService;

    protected function setUp()
    {
        $this->contactRequestBuilder = $this->createMock(ContactRequestBuilder::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->exportContactService = new ExportContactService(
            $this->contactRequestBuilder,
            $this->publisher
        );
    }

    public function testExportWithMagentoCustomer()
    {
        /** @var MockObject|Customer $magentoCustomer */
        $magentoCustomer = $this->createMock(Customer::class);

        $email = 'email@example.com';
        $request = ['request'];

        $magentoCustomer->expects($this->once())
            ->method('getData')
            ->with('email')
            ->willReturn($email);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithMagentoCustomer')
            ->with($magentoCustomer)
            ->willReturn($request);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CONTACT_EXPORT,
                json_encode(['email' => $email, 'request' => $request]));

        $this->exportContactService->exportWithMagentoCustomer($magentoCustomer);
    }

    public function testExportWithSubscriber()
    {
        /** @var MockObject|Subscriber $subscriber */
        $subscriber = $this->createMock(Subscriber::class);

        $email = 'email@example.com';
        $request = ['request'];

        $subscriber->expects($this->once())
            ->method('getData')
            ->with('email')
            ->willReturn($email);

        $this->contactRequestBuilder->expects($this->once())
            ->method('buildWithSubscriber')
            ->with($subscriber)
            ->willReturn($request);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CONTACT_EXPORT,
                json_encode(['email' => $email, 'request' => $request])
            );

        $this->exportContactService->exportWithSubscriber($subscriber);
    }
}
