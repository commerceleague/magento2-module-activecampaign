<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned;

use CommerceLeague\ActiveCampaign\Model\Abandoned;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Abandoned as AbandonedResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @codeCoverageIgnore
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Abandoned::class, AbandonedResource::class);
    }
}
