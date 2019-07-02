<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Block\Adminhtml\Contact;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class ImportButton
 */
class ImportButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Import Contacts'),
            'class' => 'primary',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->getImportUrl() . '\', {"data": {}})',
        ];
    }

    /**
     * @return string
     */
    private function getImportUrl(): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/import');
    }
}
