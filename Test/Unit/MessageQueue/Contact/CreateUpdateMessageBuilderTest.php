<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\MessageQueue\Contact;

use CommerceLeague\ActiveCampaign\Api\Data\ContactInterface;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessage;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageBuilder;
use CommerceLeague\ActiveCampaign\MessageQueue\Contact\CreateUpdateMessageFactory;
use Magento\Customer\Model\Customer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUpdateMessageBuilderTest extends TestCase
{
    /**
     * @var MockObject|CreateUpdateMessageFactory
     */
    protected $createUpdateMessageFactory;

    /**
     * @var MockObject|CreateUpdateMessage
     */
    protected $createUpdateMessage;

    /**
     * @var MockObject|ContactInterface
     */
    protected $contact;

    /**
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var CreateUpdateMessageBuilder
     */
    protected $createUpdateMessageBuilder;

    protected function setUp()
    {
        $this->createUpdateMessageFactory = $this->getMockBuilder(CreateUpdateMessageFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->createUpdateMessage = $this->createMock(CreateUpdateMessage::class);
        $this->contact = $this->createMock(ContactInterface::class);
        $this->customer = $this->createMock(Customer::class);
        $this->subscriber = $this->createMock(Subscriber::class);

        $this->createUpdateMessageBuilder = new CreateUpdateMessageBuilder(
            $this->createUpdateMessageFactory
        );
    }

    public function testBuildWithCustomer()
    {
        $email = 'example@example.com';
        $firstName = 'firstName';
        $lastName = 'lastName';

        $this->customer->expects($this->at(0))
            ->method('getData')
            ->with('email')
            ->willReturn($email);

        $this->customer->expects($this->at(1))
            ->method('getData')
            ->with('firstname')
            ->willReturn($firstName);

        $this->customer->expects($this->at(2))
            ->method('getData')
            ->with('lastname')
            ->willReturn($lastName);

        $this->createUpdateMessageFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->createUpdateMessage);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn('123');

        $this->createUpdateMessage->expects($this->once())
            ->method('setContactId')
            ->with(123)
            ->willReturnSelf();

        $request = [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ];

        $this->createUpdateMessage->expects($this->once())
            ->method('setSerializedRequest')
            ->with(json_encode($request));

        $this->assertSame(
            $this->createUpdateMessage,
            $this->createUpdateMessageBuilder->buildWithCustomer($this->contact, $this->customer)
        );
    }

    public function testBuildWithSubscriber()
    {
        $email = 'example@example.com';

        $this->createUpdateMessageFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->createUpdateMessage);

        $this->subscriber->expects($this->at(0))
            ->method('getEmail')
            ->willReturn($email);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn('123');

        $this->createUpdateMessage->expects($this->once())
            ->method('setContactId')
            ->with(123)
            ->willReturnSelf();

        $request = [
            'email' => $email
        ];

        $this->createUpdateMessage->expects($this->once())
            ->method('setSerializedRequest')
            ->with(json_encode($request));

        $this->assertSame(
            $this->createUpdateMessage,
            $this->createUpdateMessageBuilder->buildWithSubscriber($this->contact, $this->subscriber)
        );
    }
}
