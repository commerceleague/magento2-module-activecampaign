<?php

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use Magento\Customer\Model\Customer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 */
class ContactRequestBuilderTest extends TestCase
{
    /**
     * @var MockObject|Customer
     */
    protected $customer;

    /**
     * @var MockObject|Subscriber
     */
    protected $subscriber;

    /**
     * @var ContactRequestBuilder
     */
    protected $contactRequestBuilder;

    protected function setUp()
    {
        $this->customer = $this->createMock(Customer::class);
        $this->subscriber = $this->createMock(Subscriber::class);
        $this->contactRequestBuilder = new ContactRequestBuilder();
    }

    public function testBuildWithCustomer()
    {
        $email = 'email@example.com';
        $firstName = 'John';
        $lasName = 'Doe';

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
            ->willReturn($lasName);

        $this->customer->expects($this->exactly(3))
            ->method('getData');

        $this->assertEquals(
            [
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lasName
            ],
            $this->contactRequestBuilder->buildWithCustomer($this->customer)
        );
    }

    public function testBuildWithSubscriber()
    {
        $email = 'email@example.com';

        $this->subscriber->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->assertEquals(
            [
                'email' => $email
            ],
            $this->contactRequestBuilder->buildWithSubscriber($this->subscriber)
        );
    }
}
