<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Helper;

use CommerceLeague\ActiveCampaign\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
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

    public function testIsApiEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/api/enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isApiEnabled());
    }

    public function testIsApiEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/api/enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isApiEnabled());
    }

    public function testGetApiUrl()
    {
        $apiUrl = 'http://example.com';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/api/url')
            ->willReturn($apiUrl);

        $this->assertEquals($apiUrl, $this->config->getApiUrl());
    }

    public function testGetApiToken()
    {
        $apiToken = 'API_TOKEN';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/api/token')
            ->willReturn($apiToken);

        $this->assertEquals($apiToken, $this->config->getApiToken());
    }

    public function testIsEventTrackingEnabledFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/event_tracking/enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isEventTrackingEnabled());
    }

    public function testIsEventTrackingEnabledTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/event_tracking/enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isEventTrackingEnabled());
    }

    public function testGetEventTrackingId()
    {
        $trackingId = '123456';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/event_tracking/id')
            ->willReturn($trackingId);

        $this->assertEquals($trackingId, $this->config->getEventTrackingId());
    }

    public function testGetEventTrackingKey()
    {
        $trackingKey = '12bf905c03e23b67fde95145d3c9fad7edb2374ba';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/event_tracking/key')
            ->willReturn($trackingKey);

        $this->assertEquals($trackingKey, $this->config->getEventTrackingKey());
    }
}
