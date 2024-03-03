<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Model\ActiveCampaign\GuestCustomerFactory;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use CommerceLeague\ActiveCampaign\vendor\Model\ActiveCampaign\CustomerRepository;
use CommerceLeague\ActiveCampaign\vendor\Model\ActiveCampaign\GuestCustomer;
use CommerceLeague\ActiveCampaign\vendor\Model\ActiveCampaign\GuestCustomerRepository;
use CommerceLeague\ActiveCampaign\vendor\Model\ResourceModel\ActiveCampaign\GuestCustomer as CustomerResource;
use Exception;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;

class GuestCustomerRepositoryTest extends AbstractTestCase
{

    /**
     * @var MockObject|CustomerResource
     */
    protected $customerResource;

    /**
     * @var MockObject|GuestCustomer
     */
    protected $customer;

    /**
     * @var MockObject|GuestCustomerFactory
     */
    protected $customerFactory;

    /**
     * @var MockObject|MagentoCustomer
     */
    protected $magentoCustomer;

    /**
     * @var GuestCustomerRepository
     */
    protected $guestCustomerRepository;

    public function testSaveThrowsException()
    {
        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willThrowException(new Exception('an exception message'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('an exception message');

        $this->guestCustomerRepository->save($this->customer);
    }

    public function testSave()
    {
        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertSame($this->customer, $this->guestCustomerRepository->save($this->customer));
    }

    public function testGetById()
    {
        $entityId = 123;

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $entityId)
            ->willReturn($this->customer);

        $this->assertSame($this->customer, $this->guestCustomerRepository->getById($entityId));
    }

    public function testDeleteThrowsException()
    {
        $this->customerResource->expects($this->once())
            ->method('delete')
            ->with($this->customer)
            ->willThrowException(new Exception('an exception message'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('an exception message');

        $this->guestCustomerRepository->delete($this->customer);
    }

    public function testDelete()
    {
        $this->customerResource->expects($this->once())
            ->method('delete')
            ->with($this->customer)
            ->willReturnSelf();

        $this->assertTrue($this->guestCustomerRepository->delete($this->customer));
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
        $this->expectExceptionMessage('The Guest Customer with the "123" ID doesn\'t exist');

        $this->guestCustomerRepository->deleteById($entityId);
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

        $this->assertTrue($this->guestCustomerRepository->deleteById($entityId));
    }

    public function testGetOrCreateWithUnknownGuest()
    {
        $email = 'something@something.com';

        $customerData = [
            GuestCustomerInterface::FIRSTNAME => 'firstname',
            GuestCustomerInterface::LASTNAME  => 'lastname',
            GuestCustomerInterface::EMAIL     => $email
        ];

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $email, GuestCustomerInterface::EMAIL)
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->customer->expects($this->once())
            ->method('setEmail')
            ->with($customerData[GuestCustomerInterface::EMAIL])
            ->willReturnSelf();

        $this->customer->expects($this->once())
            ->method('setFirstname')
            ->with($customerData[GuestCustomerInterface::FIRSTNAME])
            ->willReturnSelf();

        $this->customer->expects($this->once())
            ->method('setLastname')
            ->with($customerData[GuestCustomerInterface::LASTNAME])
            ->willReturnSelf();

        $this->customerResource->expects($this->once())
            ->method('save')
            ->with($this->customer)
            ->willReturn($this->customer);

        $this->assertSame($this->customer, $this->guestCustomerRepository->getOrCreate($customerData));
    }


    public function testGetOrCreateWithKnownGuest()
    {
        $email = 'something@something.com';

        $customerData = [
            GuestCustomerInterface::FIRSTNAME => 'firstname',
            GuestCustomerInterface::LASTNAME  => 'lastname',
            GuestCustomerInterface::EMAIL     => $email
        ];

        $this->customerResource->expects($this->once())
            ->method('load')
            ->with($this->customer, $email, GuestCustomerInterface::EMAIL)
            ->willReturn($this->customer);

        $this->customer->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->customer->expects($this->never())
            ->method('setEmail');

        $this->customer->expects($this->never())
            ->method('setFirstname');

        $this->customer->expects($this->never())
            ->method('setLastname');

        $this->customerResource->expects($this->never())
            ->method('save');

        $this->assertSame($this->customer, $this->guestCustomerRepository->getOrCreate($customerData));
    }

    protected function setUp(): void
    {
        $this->customerResource = $this->getMockBuilder(CustomerResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory = $this->getMockBuilder(GuestCustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customer = $this->getMockBuilder(GuestCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customer);

        $this->magentoCustomerRepository = $this->createMock(
            \Magento\Customer\Model\ResourceModel\CustomerRepository::class
        );

        $this->customerRepository = $this->createMock(
            CustomerRepository::class
        );

        $this->guestCustomerRepository = new GuestCustomerRepository(
            $this->customerResource, $this->customerFactory, $this->magentoCustomerRepository, $this->customerRepository
        );
    }
}
