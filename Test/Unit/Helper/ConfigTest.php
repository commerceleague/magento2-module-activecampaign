<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Helper;

use CommerceLeague\ActiveCampaign\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var MockObject|Context
     */
    protected $context;

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
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);

        $this->context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfig);

        $this->config = $objectManagerHelper->getObject(
            Config::class,
            [
                'context' => $this->context
            ]
        );
    }

    public function testIsEnabledIsFalse()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/general/enabled')
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function testIsEnabledIsTrue()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('activecampaign/general/enabled')
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testGetApiKeyWithoutConfigSet()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/general/api_key')
            ->willReturn(null);

        $this->assertNull($this->config->getApiKey());
    }

    public function testGetApiKey()
    {
        $apiKey = 'THE_API_KEY';

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('activecampaign/general/api_key')
            ->willReturn($apiKey);

        $this->assertEquals($apiKey, $this->config->getApiKey());
    }
}
