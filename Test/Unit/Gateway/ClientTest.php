<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Gateway;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var MockObject|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->client = new Client($this->configHelper);
    }

    public function testGetCommonClient()
    {
        $this->configHelper->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('http://example.com');

        $this->configHelper->expects($this->once())
            ->method('getApiToken')
            ->willReturn('API_TOKEN');

        $this->client->getAbandonedCartApi();
    }
}
