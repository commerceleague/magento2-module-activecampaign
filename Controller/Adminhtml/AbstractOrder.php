<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPage;

/**
 * Class AbstractOrder
 */
abstract class AbstractOrder extends Action
{
    final public const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_order';

    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::activecampaign_order')
            ->addBreadcrumb(__('ActiveCampaign')->getText(), __('ActiveCampaign')->getText())
            ->addBreadcrumb(__('Orders')->getText(), __('Orders')->getText());

        return $resultPage;
    }
}
