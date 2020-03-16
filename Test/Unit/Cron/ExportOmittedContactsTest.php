<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Cron;

use CommerceLeague\ActiveCampaign\Cron\PublishOmittedContacts;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\Collection as CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportOmittedContactsTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var MockObject|CustomerCollection
     */
    protected $customerCollection;

    /**
     * @var MockObject|SubscriberCollectionFactory
     */
    protected $subscriberCollectionFactory;

    /**
     * @var MockObject|SubscriberCollection
     */
    protected $subscriberCollection;

    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var PublishOmittedContacts
     */
    protected $exportOmittedContacts;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);

        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->subscriberCollectionFactory = $this->getMockBuilder(SubscriberCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->subscriberCollection = $this->createMock(SubscriberCollection::class);

        $this->subscriberCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->subscriberCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->exportOmittedContacts = new PublishOmittedContacts(
            $this->configHelper,
            $this->customerCollectionFactory,
            $this->subscriberCollectionFactory,
            $this->publisher
        );
    }

    public function testRunDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->never())
            ->method('addContactOmittedFilter');

        $this->exportOmittedContacts->run();
    }

    public function testRunContactExportDisabled()
    {
        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isContactExportEnabled')
            ->willReturn(false);

        $this->customerCollection->expects($this->never())
            ->method('addContactOmittedFilter');

        $this->exportOmittedContacts->run();
    }

    public function testRun()
    {
        $customerIds = [123, 456];
        $emails = ['example1@example.com', 'example2@example.com'];

        $this->configHelper->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->configHelper->expects($this->once())
            ->method('isContactExportEnabled')
            ->willReturn(true);

        $this->customerCollection->expects($this->once())
            ->method('addContactOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $this->subscriberCollection->expects($this->once())
            ->method('excludeCustomers')
            ->willReturnSelf();

        $this->subscriberCollection->expects($this->once())
            ->method('addContactOmittedFilter')
            ->willReturnSelf();

        $this->subscriberCollection->expects($this->once())
            ->method('getAllEmails')
            ->willReturn($emails);

        $this->publisher->expects($this->exactly(4))
            ->method('publish');

        $this->publisher->expects($this->at(0))
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $customerIds[0]])
            );

        $this->publisher->expects($this->at(1))
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $customerIds[1]])
            );

        $this->publisher->expects($this->at(2))
            ->method('publish')
            ->with(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $emails[0]])
            );

        $this->publisher->expects($this->at(3))
            ->method('publish')
            ->with(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $emails[1]])
            );

        $this->exportOmittedContacts->run();
    }
}
