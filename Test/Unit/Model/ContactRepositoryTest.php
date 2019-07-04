<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Model;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ContactRepository;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
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
     * @var MockObject|Contact
     */
    protected $contact;

    /**
     * @var MockObject|ContactFactory
     */
    protected $contactFactory;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

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

        $this->subscriber = $this->getMockBuilder(Subscriber::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->assertSame($this->contact, $this->contactRepository->save($this->contact));
    }

    public function testGetById()
    {
        $entityId = 123;

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $entityId)
            ->willReturn($this->contact);

        $this->assertSame($this->contact, $this->contactRepository->getById($entityId));
    }

    public function testGetByEmail()
    {
        $email = 'email@example.com';

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $email, ContactInterface::EMAIL)
            ->willReturn($this->contact);

        $this->assertSame($this->contact, $this->contactRepository->getByEmail($email));
    }

    public function testGetOrCreateByEmailWithKnownContact()
    {
        $email = 'email@example.com';

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $email, ContactInterface::EMAIL)
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->contact->expects($this->never())
            ->method('setEmail');

        $this->assertSame($this->contact, $this->contactRepository->getOrCreateByEmail($email));
    }

    public function testGetOrCreateByEmail()
    {
        $email = 'email@example.com';

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $email, ContactInterface::EMAIL)
            ->willReturn($this->contact);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->contact->expects($this->once())
            ->method('setEmail')
            ->with($email)
            ->willReturnSelf();

        $this->contactResource->expects($this->once())
            ->method('save')
            ->with($this->contact)
            ->willReturn($this->contact);

        $this->assertSame($this->contact, $this->contactRepository->getOrCreateByEmail($email));
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
        $entityId = 123;

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $entityId)
            ->willReturn($this->contact);

        $this->contactResource->expects($this->never())
            ->method('delete');

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The Contact with the "123" ID doesn\'t exist');

        $this->contactRepository->deleteById($entityId);
    }

    public function testDeleteById()
    {
        $entityId = 123;

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->contactResource->expects($this->once())
            ->method('load')
            ->with($this->contact, $entityId)
            ->willReturn($this->contact);

        $this->contactResource->expects($this->once())
            ->method('delete')
            ->with($this->contact)
            ->willReturnSelf();

        $this->assertTrue($this->contactRepository->deleteById($entityId));
    }
}
