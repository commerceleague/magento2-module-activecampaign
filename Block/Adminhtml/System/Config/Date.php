<?php
declare(strict_types=1);

namespace CommerceLeague\ActiveCampaign\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Date
 *
 * @package CommerceLeague\ActiveCampaign\Block\Adminhtml\System\Config
 */
class Date extends Field
{

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        return parent::render($element);
    }
}