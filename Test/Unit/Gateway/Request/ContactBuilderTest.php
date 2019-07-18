<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder;
use CommerceLeague\ActiveCampaign\Helper\Contants;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;

class ContactBuilderTest extends TestCase
{
    /**
     * @var MockObject|MagentoCustomerInterface
     */
    protected $magentoCustomer;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var MockObject|ExtensionAttributesInterface
     */
    protected $extensionAttributes;

    /**
     * @var ContactBuilder
     */
    protected $contactBuilder;

    protected function setUp()
    {
        $this->magentoCustomer = $this->createMock(MagentoCustomerInterface::class);
        $this->subscriber = $this->createMock(Subscriber::class);
        $this->extensionAttributes = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->setMethods(['getIsSubscribed'])
            ->getMockForAbstractClass();

        $this->contactBuilder = new ContactBuilder();
    }

    public function testBuildWithMagentoCustomerNotSubscribed()
    {
        $email = 'email@example.com';
        $firstName = 'firstName';
        $lastName = 'lastName';

        $this->magentoCustomer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);

        $this->extensionAttributes->expects($this->once())
            ->method('getIsSubscribed')
            ->willReturn(false);

        $this->magentoCustomer->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->magentoCustomer->expects($this->once())
            ->method('getFirstname')
            ->willReturn($firstName);

        $this->magentoCustomer->expects($this->once())
            ->method('getLastname')
            ->willReturn($lastName);

        $expected = [
            'status' => Contants::CONTACT_STATUS_UNSUBSCRIBED,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ];

        $this->assertEquals(
            $expected,
            $this->contactBuilder->buildWithMagentoCustomer($this->magentoCustomer)
        );
    }


    public function testBuildWithMagentoCustomerSubscribed()
    {
        $email = 'email@example.com';
        $firstName = 'firstName';
        $lastName = 'lastName';

        $this->magentoCustomer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);

        $this->extensionAttributes->expects($this->once())
            ->method('getIsSubscribed')
            ->willReturn(true);

        $this->magentoCustomer->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->magentoCustomer->expects($this->once())
            ->method('getFirstname')
            ->willReturn($firstName);

        $this->magentoCustomer->expects($this->once())
            ->method('getLastname')
            ->willReturn($lastName);

        $expected = [
            'status' => Contants::CONTACT_STATUS_ACTIVE,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ];

        $this->assertEquals(
            $expected,
            $this->contactBuilder->buildWithMagentoCustomer($this->magentoCustomer)
        );
    }

    public function testBuildWithSubscriberNotSubscribed()
    {
        $email = 'email@example.com';

        $this->subscriber->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $expected = [
            'status' => Contants::CONTACT_STATUS_UNSUBSCRIBED,
            'email' => $email
        ];

        $this->assertEquals(
            $expected,
            $this->contactBuilder->buildWithSubscriber($this->subscriber)
        );
    }

    public function testBuildWithSubscriberSubscribed()
    {
        $email = 'email@example.com';

        $this->subscriber->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(true);

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $expected = [
            'status' => Contants::CONTACT_STATUS_ACTIVE,
            'email' => $email
        ];

        $this->assertEquals(
            $expected,
            $this->contactBuilder->buildWithSubscriber($this->subscriber)
        );
    }

}
