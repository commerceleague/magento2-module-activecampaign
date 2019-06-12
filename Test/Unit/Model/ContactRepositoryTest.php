<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ContactRepository;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
use CommerceLeague\ActiveCampaign\Model\ContactFactory;

class ContactRepositoryTest extends TestCase
{
    /**
     * @var MockObject|ContactResource
     */
    protected $contactResource;

    /**
     * @var MockObject|ContactFactory
     */
    protected $contactFactory;

    /**
     * @var MockObject|Contact
     */
    protected $contact;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    protected function setUp()
    {
        $this->contactResource = $this->getMockBuilder(ContactResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactFactory = $this->getMockBuilder(ContactFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->contact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->contact);

        $this->contactRepository = new ContactRepository(
            $this->contactResource,
            $this->contactFactory
        );
    }

    public function testSaveThrowsException()
    {
        $this->contactResource->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('an exception message');

        $this->contactRepository->save($this->contact);
    }

    public function testSave()
    {
        $this->contactResource->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willReturnSelf();

        $this->assertEquals($this->contact, $this->contactRepository->save($this->contact));
    }

    public function testGetByIdThrowsException()
    {
        $contactId = 123;

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $contactId)
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(false);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Contact with the "123" ID doesn\'t exist');

        $this->contactRepository->getById($contactId);
    }

    public function testGetById()
    {
        $contactId = 123;

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->assertEquals($this->contact, $this->contactRepository->getById($contactId));
    }

    public function testGetByCustomerIdThrowsException()
    {
        $customerId = 456;

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $customerId, 'customer_id')
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(false);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Contact with the "456" customer ID doesn\'t exist');

        $this->contactRepository->getByCustomerId($customerId);
    }

    public function testGetByCustomerId()
    {
        $contactId = 123;
        $customerId = 456;

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $customerId, 'customer_id')
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->assertEquals($this->contact, $this->contactRepository->getByCustomerId($customerId));
    }

    public function testDeleteThrowsException()
    {
        $this->contactResource->expects($this->once())
            ->method('delete')
            ->with($this->contact)
            ->willThrowException(new \Exception('an exception message'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('an exception message');

        $this->contactRepository->delete($this->contact);
    }

    public function testDelete()
    {
        $this->contactResource->expects($this->once())
            ->method('delete')
            ->with($this->contact)
            ->willReturnSelf();

        $this->assertTrue($this->contactRepository->delete($this->contact));
    }

    public function testDeleteByIdThrowsException()
    {
        $contactId = 123;

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(true);

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $contactId)
            ->willReturn($this->contact);

        $this->contactResource->expects($this->once())
            ->method('delete')
            ->with($this->contact)
            ->willReturnSelf();

        $this->assertTrue($this->contactRepository->deleteById($contactId));
    }
}
