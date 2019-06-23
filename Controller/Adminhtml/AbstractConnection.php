<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPageModel;
use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Class AbstractConnection
 */
abstract class AbstractConnection extends Action
{
    const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::connection';

    /**
     * @param ResultPage|ResultPageModel $resultPage
     * @return ResultPageModel
     */
    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::connection')
            ->addBreadcrumb(__('ActiveCampaign'), __('ActiveCampaign'))
            ->addBreadcrumb(__('Connections'), __('Connections'));

        return $resultPage;
    }
}
