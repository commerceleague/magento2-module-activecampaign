<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Helper;

use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaign\Test\Unit\AbstractTestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigTest extends AbstractTestCase
{

    /**
     * @var MockObject|ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $config;

    protected function setUp()
    {
        $this->scopeConfig = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );

        $objectManager = new ObjectManager($this);

        $this->config = $objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    public function testIsEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/general/enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function testIsEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/general/enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testGetApiUrl()
    {
        $apiUrl = 'http://example.com';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/general/api_url')
            ->willReturn($apiUrl);

        $this->assertEquals($apiUrl, $this->config->getApiUrl());
    }

    public function testGetApiToken()
    {
        $apiToken = 'API_TOKEN';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/general/api_token')
            ->willReturn($apiToken);

        $this->assertEquals($apiToken, $this->config->getApiToken());
    }

    public function testGetConnectionId()
    {
        $connectionId = '123';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/general/connection_id')
            ->willReturn($connectionId);

        $this->assertEquals($connectionId, $this->config->getConnectionId());
    }

    public function testIsContactExportEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/contact_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isContactExportEnabled());
    }

    public function testIsContactExportEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/contact_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isContactExportEnabled());
    }

    public function testIsCustomerExportEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/customer_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isCustomerExportEnabled());
    }

    public function testIsCustomerExportEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/customer_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isCustomerExportEnabled());
    }

    public function testIsOrderExportEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/order_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isOrderExportEnabled());
    }

    public function testIsOrderExportEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/order_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isOrderExportEnabled());
    }

    public function testIsAbandonedCartExportEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/abandoned_cart_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isAbandonedCartExportEnabled());
    }

    public function testIsAbandonedCartExportEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/abandoned_cart_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isAbandonedCartExportEnabled());
    }

    public function testIsWebhookEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/webhook/enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isWebhookEnabled());
    }

    public function testIsWebhookEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/webhook/enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isWebhookEnabled());
    }

    public function testGetWebhookToken()
    {
        $token = 'THE_TOKEN';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/webhook/token')
            ->willReturn($token);

        $this->assertEquals($token, $this->config->getWebhookToken());
    }
}
