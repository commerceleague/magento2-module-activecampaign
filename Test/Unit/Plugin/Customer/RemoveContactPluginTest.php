<?php
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Plugin\Customer;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Plugin\Customer\RemoveContactPlugin;
use PHPUnit\Framework\TestCase;

class RemoveContactPluginTest extends TestCase
{
    protected function setUp()
    {
        $this->contactRepository = $this->getMockBuilder(ContactRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}
