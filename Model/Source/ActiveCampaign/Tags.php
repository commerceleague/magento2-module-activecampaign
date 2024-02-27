<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Model\Source\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Tags
 *
 * @package CommerceLeague\ActiveCampaign\Model\Source\ActiveCampaign
 */
class Tags implements ArrayInterface
{

    private array $options = [];

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {

        if (count($this->options) == 0) {
            $options = [];
            $page    = $this->client->getTagsApi()->listPerPage(100);
            $lists   = $page->getItems();
            foreach ($lists as $list) {
                $options[] = [
                    'value' => $list['id'],
                    'label' => $list['tag']
                ];
            }
            $this->options = $options;
        }
        return $this->options;
    }
}