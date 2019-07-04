<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Gateway\Request\ContactBuilder;
use Magento\Customer\Model\Customer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactBuilderTest extends TestCase
{
    public function testBuildWithMagentoCustomer()
    {
        $email = 'email@example.com';
        $firstName = 'firstName';
        $lastName = 'lastName';

        /** @var MockObject|Customer $magentoCustomer */
        $magentoCustomer = $this->createMock(Customer::class);

        $magentoCustomer->expects($this->at(0))
            ->method('getData')
            ->with('email')
            ->willReturn($email);

        $magentoCustomer->expects($this->at(1))
            ->method('getData')
            ->with('firstname')
            ->willReturn($firstName);

        $magentoCustomer->expects($this->at(2))
            ->method('getData')
            ->with('lastname')
            ->willReturn($lastName);

        $expected = [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ];

        $this->assertEquals(
            $expected,
            (new ContactBuilder())->buildWithMagentoCustomer($magentoCustomer)
        );
    }

    public function testBuildWithSubscriber()
    {
        /** @var MockObject|Subscriber $subscriber */
        $subscriber = $this->createMock(Subscriber::class);

        $email = 'email@example.com';

        $subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->assertEquals(
            ['email' => $email],
            (new ContactBuilder())->buildWithSubscriber($subscriber)
        );
    }

}
