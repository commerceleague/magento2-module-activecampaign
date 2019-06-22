<?php

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Gateway\Request\ContactRequestBuilder;
use Magento\Customer\Model\Customer;
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
     * @var ContactRequestBuilder
     */
    protected $contactRequestBuilder;

    protected function setUp()
    {
        $this->customer = $this->createMock(Customer::class);
        $this->contactRequestBuilder = new ContactRequestBuilder();
    }

    public function testBuild()
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
            $this->contactRequestBuilder->build($this->customer)
        );
    }
}
