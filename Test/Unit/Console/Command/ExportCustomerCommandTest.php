<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use CommerceLeague\ActiveCampaign\Console\Command\ExportCustomerCommand;
use CommerceLeague\ActiveCampaign\MessageQueue\Topics;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Tester\CommandTester;

class ExportCustomerCommandTest extends TestCase
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
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var MockObject|ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var ExportCustomerCommand
     */
    protected $exportCustomerCommand;

    /**
     * @var CommandTester
     */
    protected $exportCustomerCommandTester;

    public function setUp()
    {
        $this->customerCollectionFactory = $this->getMockBuilder(CustomerCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerCollection = $this->createMock(CustomerCollection::class);

        $this->customerCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerCollection);

        $this->publisher = $this->createMock(PublisherInterface::class);

        $this->progressBarFactory = $this->getMockBuilder(ProgressBarFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->exportCustomerCommand = new ExportCustomerCommand(
            $this->customerCollectionFactory,
            $this->publisher,
            $this->progressBarFactory
        );

        $this->exportCustomerCommandTester = new CommandTester($this->exportCustomerCommand);
    }

    public function testExecuteWithoutOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please provide at least one option');

        $this->exportCustomerCommandTester->execute([]);
    }

    public function testExecuteWithEmailAndOtherOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --email together with another option');

        $this->exportCustomerCommandTester->execute(
            ['--email' => 'email@example.com', '--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithAllAndOmittedOption()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You cannot use --omitted and --all together');

        $this->exportCustomerCommandTester->execute(
            ['--omitted' => true, '--all' => true]
        );
    }

    public function testExecuteWithoutCustomerIds()
    {
        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([]);

        $this->exportCustomerCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            'No customer(s) found matching your criteria',
            $this->exportCustomerCommandTester->getDisplay()
        );

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $this->exportCustomerCommandTester->getStatusCode()
        );
    }

    public function testExecuteWithEmailOption()
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
                Topics::CUSTOMER_CUSTOMER_EXPORT,
                json_encode(['magento_customer_id' => $customerId])
            );

        $this->exportCustomerCommandTester->execute(
            ['--email' => $email]
        );

        $this->assertContains(
            '1 customers(s) have been scheduled for export.',
            $this->exportCustomerCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportCustomerCommandTester->getStatusCode());
    }

    public function testExecuteWithOmittedOption()
    {
        $customerIds = [123, 456];

        $this->customerCollection->expects($this->never())
            ->method('addEmailFilter');

        $this->customerCollection->expects($this->once())
            ->method('addCustomerOmittedFilter')
            ->willReturnSelf();

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(2))
            ->method('publish');

        $this->exportCustomerCommandTester->execute(
            ['--omitted' => true]
        );

        $this->assertContains(
            '2 customers(s) have been scheduled for export.',
            $this->exportCustomerCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportCustomerCommandTester->getStatusCode());
    }


    public function testExecuteWithAllOption()
    {
        $customerIds = [123, 456, 789];

        $this->customerCollection->expects($this->never())
            ->method('addCustomerOmittedFilter');

        $this->customerCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn($customerIds);

        $progressBar = new ProgressBar(new TestOutput());

        $this->progressBarFactory->expects($this->once())
            ->method('create')
            ->willReturn($progressBar);

        $this->publisher->expects($this->exactly(3))
            ->method('publish');

        $this->exportCustomerCommandTester->execute(
            ['--all' => true]
        );

        $this->assertContains(
            '3 customers(s) have been scheduled for export.',
            $this->exportCustomerCommandTester->getDisplay()
        );

        $this->assertEquals(Cli::RETURN_SUCCESS, $this->exportCustomerCommandTester->getStatusCode());
    }
}
