<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace CommerceLeague\ActiveCampaign\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class TestConnectionButton
 *
 * @package CommerceLeague\ActiveCampaign\Block\Adminhtml\System
 */
class Connection extends Field
{

    /**
     * @var string
     */
    protected $_template = 'CommerceLeague_ActiveCampaign::system/config/connection.phtml';

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array   $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return ajax url for synchronize button
     *
     * @return string
     */
    public function getAjaxConnectionUrl()
    {
        return $this->getUrl('activecampaign/ajax/connectionChecker');
    }

    /**
     * Generate synchronize button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'activecampaign_connect',
                'label' => __('Test Connection'),
                'class' => 'primary'
            ]
        );

        return $button->toHtml();
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
