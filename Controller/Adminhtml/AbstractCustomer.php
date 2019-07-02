<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPageModel;
use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Class AbstractCustomer
 */
abstract class AbstractCustomer extends Action
{
    const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_customer';

    /**
     * @param ResultPage|ResultPageModel $resultPage
     * @return ResultPage
     */
    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::activecampaign_customer')
            ->addBreadcrumb(__('ActiveCampaign'), __('ActiveCampaign'))
            ->addBreadcrumb(__('Customers'), __('Customers'));

        return $resultPage;
    }
}
