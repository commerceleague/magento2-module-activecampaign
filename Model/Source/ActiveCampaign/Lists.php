<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Model\Source\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnauthorizedHttpException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Lists
 *
 */
class Lists implements ArrayInterface
{

    private array $options = [];

    /**
     * @var CommonClient
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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
            try {
                $page  = $this->client->getListsApi()->listPerPage(100);
                $lists = $page->getItems();
                foreach ($lists as $list) {
                    $options[] = [
                        'value' => $list['id'],
                        'label' => $list['name']
                    ];
                }
            } catch (UnauthorizedHttpException $exception) {
                // fail silently for the moment
            } catch (NotFoundHttpException $exception) {
                // fail silently for the moment
            } catch (InvalidArgumentException $exception) {
                // fail silently for the moment
            }

            $this->options = $options;
        }
        return $this->options;
    }
}
