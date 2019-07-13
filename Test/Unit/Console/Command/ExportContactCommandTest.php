<?php
declare(strict_types=1);
/**
 */

namespace Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\ExportContactCommand;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Magento\CustomerCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Magento\CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Magento\SubscriberCollection;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Magento\SubscriberCollectionFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\Console\Command\TestOutput;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group current
 */
class ExportContactCommandTest extends TestCase
{
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
     * @var MockObject|ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var ExportContactCommand
     */
    protected $exportContactCommand;

    /**
     * @var CommandTester
     */
    protected $exportContactCommandTester;

    protected function setUp()
    {
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

        $this->progressBarFactory = $this->getMockBuilder(ProgressBarFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->exportContactCommand = new ExportContactCommand(
            $this->customerCollectionFactory,
            $this->subscriberCollectionFactory,
            $this->publisher,
            $this->progressBarFactory
        );

        $this->exportContactCommandTester = new CommandTester($this->exportContactCommand);
    }


    public function testExecuteWithoutOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide at least one option');

        $this->exportContactCommandTester->execute([]);
    }

    public function testExecuteWithEmailAndOtherOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --email together with another option');

        $this->exportContactCommandTester->execute(
            ['--email' => 'email@example.com', '--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithAllAndOmittedOption()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --omitted and --all together');

        $this->exportContactCommandTester->execute(
            ['--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithoutCustomerIdsAndSubscriberEmails()
    {
        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([]);

        $this->subscriberCollection->expects($this->once())
            ->method('getAllEmails')
            ->willReturn([]);

        $this->exportContactCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            'No contact(s) found matching your criteria',
            $this->exportContactCommandTester->getDisplay()
        );

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $this->exportContactCommandTester->getStatusCode()
        );
    }

    public function testExecuteWithEmailFromCustomerOption()
    {
        $email = 'email@example.com';
        $customerId = 123;

        $this->customerCollection->expects($this->once())
            ->method('addEmailFilter')
            ->with($email)
            ->willReturnSelf();

        $this->customerCollection->expects($this->never())
            ->method('addCustomerOmittedFilter');

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([$customerId]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::CUSTOMER_CONTACT_EXPORT,
                json_encode(['magento_customer_id' => $customerId])
            );

        $this->exportContactCommandTester->execute(
            ['--email' => $email]
        );

        $this->assertContains(
            '1 contact(s) have been scheduled for export.',
            $this->exportContactCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportContactCommandTester->getStatusCode());
    }

    public function testExecuteWithEmailFromSubscriberOption()
    {
        $email = 'email@example.com';

        $this->customerCollection->expects($this->once())
            ->method('addEmailFilter')
            ->with($email)
            ->willReturnSelf();

        $this->customerCollection->expects($this->never())
            ->method('addCustomerOmittedFilter');

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([]);

        $this->subscriberCollection->expects($this->once())
            ->method('excludeCustomers')
            ->willReturnSelf();

        $this->subscriberCollection->expects($this->once())
            ->method('addEmailFilter')
            ->willReturnSelf();

        $this->subscriberCollection->expects($this->never())
            ->method('addContactOmittedFilter');

        $this->subscriberCollection->expects($this->once())
            ->method('getAllEmails')
            ->willReturn([$email]);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(
                Topics::NEWSLETTER_CONTACT_EXPORT,
                json_encode(['email' => $email])
            );

        $this->exportContactCommandTester->execute(
            ['--email' => $email]
        );

        $this->assertContains(
            '1 contact(s) have been scheduled for export.',
            $this->exportContactCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportContactCommandTester->getStatusCode());
    }

    public function testExecuteWithOmittedOption()
    {
        $customerIds = [123, 456];
        $emails = ['example1@example.com', 'example2@example.com'];

        $this->customerCollection->expects($this->never())
            ->method('addEmailFilter');

        $this->customerCollection->expects($this->once())
            ->method('addContactOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $this->subscriberCollection->expects($this->never())
            ->method('addEmailFilter');

        $this->subscriberCollection->expects($this->once())
            ->method('addContactOmittedFilter')
            ->willReturnSelf();

        $this->subscriberCollection->expects($this->once())
            ->method('getAllEmails')
            ->willReturn($emails);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(4))
            ->method('publish');

        $this->exportContactCommandTester->execute(
            ['--omitted' => true]
        );

        $this->assertContains(
            '4 contact(s) have been scheduled for export.',
            $this->exportContactCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportContactCommandTester->getStatusCode());
    }


    public function testExecuteWithAllOption()
    {
        $customerIds = [123, 456];
        $emails = ['example1@example.com', 'example2@example.com'];

        $this->customerCollection->expects($this->never())
            ->method('addEmailFilter');

        $this->customerCollection->expects($this->never())
            ->method('addContactOmittedFilter');

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $this->subscriberCollection->expects($this->never())
            ->method('addEmailFilter');

        $this->subscriberCollection->expects($this->never())
            ->method('addContactOmittedFilter');

        $this->subscriberCollection->expects($this->once())
            ->method('getAllEmails')
            ->willReturn($emails);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(4))
            ->method('publish');

        $this->exportContactCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            '4 contact(s) have been scheduled for export.',
            $this->exportContactCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportContactCommandTester->getStatusCode());
    }
}
