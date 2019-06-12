<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact;

use CommerceLeague\ActiveCampaign\Model\Contact;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Contact::class, ContactResource::class);
    }
}
