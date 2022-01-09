<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway\Request;

use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomerInterface;
use Magento\Framework\Api\ExtensionAttributesInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerBuilderTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var MockObject|MagentoCustomerInterface
     */
    protected $magentoCustomer;

    /**
     * @var MockObject|ExtensionAttributesInterface
     */
    protected $extensionAttributes;

    /**
     * @var CustomerBuilder
     */
    protected $customerBuilder;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->magentoCustomer = $this->createMock(MagentoCustomerInterface::class);
        $this->extensionAttributes = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->setMethods(['getIsSubscribed'])
            ->getMockForAbstractClass();

        $this->customerBuilder = new CustomerBuilder(
            $this->configHelper
        );
    }

    public function testBuildSubscribed()
    {
        $connectionId = 123;
        $magentoCustomerId = 456;
        $email = 'example@example.com';

        $this->configHelper->expects($this->once())
            ->method('getConnectionId')
            ->willReturn($connectionId);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($magentoCustomerId);

        $this->magentoCustomer->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $expected = [
            'connectionid' => $connectionId,
            'externalid' => $magentoCustomerId,
            'email' => $email,
            'acceptsMarketing' => 1
        ];

        $this->assertEquals(
            $expected,
            $this->customerBuilder->build($this->magentoCustomer)
        );
    }


}
