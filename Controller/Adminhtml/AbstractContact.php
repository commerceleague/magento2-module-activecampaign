<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPageModel;
use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Class AbstractContact
 */
abstract class AbstractContact extends Action
{
    const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::contact';

    /**
     * @param ResultPage|ResultPageModel $resultPage
     * @return ResultPage
     */
    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::contact')
            ->addBreadcrumb(__('ActiveCampaign'), __('ActiveCampaign'))
            ->addBreadcrumb(__('Contacts'), __('Contacts'));

        return $resultPage;
    }
}
