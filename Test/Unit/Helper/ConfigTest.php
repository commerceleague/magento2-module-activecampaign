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

    protected function setUp(): void
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

    public function testIsTombstoneSelfHealEnabledDefaultsToFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/tombstone_selfheal_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isTombstoneSelfHealEnabled());
    }

    public function testIsTombstoneSelfHealEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/tombstone_selfheal_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isTombstoneSelfHealEnabled());
    }

    public function testIsRelinkCronEnabledDefaultsToFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/relink_cron_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isRelinkCronEnabled());
    }

    public function testIsRelinkCronEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/relink_cron_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isRelinkCronEnabled());
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

    public function testGetMaxExportAttemptsDefaultsToZero()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/export/max_attempts')
            ->willReturn(null);

        $this->assertSame(0, $this->config->getMaxExportAttempts());
    }

    public function testGetMaxExportAttemptsReturnsCastValue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/export/max_attempts')
            ->willReturn('7');

        $this->assertSame(7, $this->config->getMaxExportAttempts());
    }

    public function testIsDeadLetterEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/dead_letter_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isDeadLetterEnabled());
    }

    public function testIsDeadLetterEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/dead_letter_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isDeadLetterEnabled());
    }

    public function testIsBackoffEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/backoff_enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isBackoffEnabled());
    }

    public function testIsBackoffEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/backoff_enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isBackoffEnabled());
    }

    public function testGetBackoffThresholdDefaultsToFiveWhenUnset()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/export/backoff_threshold')
            ->willReturn(null);

        $this->assertSame(5, $this->config->getBackoffThreshold());
    }

    public function testGetBackoffThresholdDefaultsToFiveWhenBelowOne()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/export/backoff_threshold')
            ->willReturn('0');

        $this->assertSame(5, $this->config->getBackoffThreshold());
    }

    public function testGetBackoffThresholdReturnsCastValue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/export/backoff_threshold')
            ->willReturn('10');

        $this->assertSame(10, $this->config->getBackoffThreshold());
    }

    public function testIsRetryAllOmittedEnabledDefaultsToFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/retry_all_omitted')
            ->willReturn(false);

        $this->assertFalse($this->config->isRetryAllOmittedEnabled());
    }

    public function testIsRetryAllOmittedEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/export/retry_all_omitted')
            ->willReturn(true);

        $this->assertTrue($this->config->isRetryAllOmittedEnabled());
    }
}
