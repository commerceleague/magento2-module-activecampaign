<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Model\Customer;
use CommerceLeague\ActiveCampaign\Model\CustomerRepository;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Customer as CustomerResource;
use CommerceLeague\ActiveCampaign\Model\CustomerFactory;

class CustomerRepositoryTest extends TestCase
{
    /**
     * @var MockObject|CustomerResource
     */
    protected $customerResource;

    /**
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var MockObject|CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var MockObject|MagentoCustomer
     */
    protected $magentoCustomer;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    protected function setUp()
    {
        $this->customerResource = $this->getMockBuilder(CustomerResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customer);

        $this->magentoCustomer = $this->getMockBuilder(MagentoCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository = new CustomerRepository(
            $this->customerResource,
            $this->customerFactory
        );
    }

    public function testSaveThrowsException()
    {
        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('an exception message');

        $this->customerRepository->save($this->customer);
    }

    public function testSave()
    {
        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertEquals($this->customer, $this->customerRepository->save($this->customer));
    }

    public function testGetById()
    {
        $entityId = 123;
        $this->assertEquals($this->customer, $this->customerRepository->getById($entityId));
    }

    public function testGetByMagentoCustomerWithUnknownMagentoCustomer()
    {
        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, null, 'magento_customer_id')
            ->willReturn($this->customer);

        $this->assertSame($this->customer, $this->customerRepository->getByMagentoCustomer($this->magentoCustomer));
    }

    public function testGetByMagentoCustomer()
    {
        $magentoCustomerId = 123;

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $magentoCustomerId, 'magento_customer_id')
            ->willReturn($this->customer);

        $this->assertSame($this->customer, $this->customerRepository->getByMagentoCustomer($this->magentoCustomer));
    }

    public function testGetOrCreateByMagentoCustomerCreatesCustomer()
    {
        $magentoCustomerId = 123;

        $this->magentoCustomer->expects($this->any())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $magentoCustomerId, 'magento_customer_id')
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->customer->expects($this->once())
            ->method('setMagentoCustomerId')
            ->with($magentoCustomerId)
            ->willReturnSelf();

        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertSame(
            $this->customer,
            $this->customerRepository->getOrCreateByMagentoCustomer($this->magentoCustomer)
        );
    }

    public function testGetOrCreateByMagentoCustomerLoadsCustomer()
    {
        $entityId = 678;
        $magentoCustomerId = 123;

        $this->magentoCustomer->expects($this->any())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $magentoCustomerId, 'magento_customer_id')
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->customer->expects($this->never())
            ->method('setMagentoCustomerId');

        $this->customerResource->expects($this->never())
            ->method('save');

        $this->assertSame(
            $this->customer,
            $this->customerRepository->getOrCreateByMagentoCustomer($this->magentoCustomer)
        );
    }

    public function testDeleteThrowsException()
    {
        $this->customerResource->expects($this->once())
            ->method('delete')
            ->with($this->customer)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('an exception message');

        $this->customerRepository->delete($this->customer);
    }

    public function testDelete()
    {
        $this->customerResource->expects($this->once())
            ->method('delete')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertTrue($this->customerRepository->delete($this->customer));
    }

    public function testDeleteByIdThrowsException()
    {
        $entityId = 123;

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $entityId)
            ->willReturn($this->customer);

        $this->customerResource->expects($this->never())
            ->method('delete');

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Customer with the "123" ID doesn\'t exist');

        $this->customerRepository->deleteById($entityId);
    }

    public function testDeleteById()
    {
        $entityId = 123;

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $entityId)
            ->willReturn($this->customer);

        $this->customerResource->expects($this->once())
            ->method('delete')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertTrue($this->customerRepository->deleteById($entityId));
    }
}
