<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPage;


/**
 * Class AbstractAbandoned
 */
abstract class AbstractAbandoned extends Action
{

    public const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_abandoned';

    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::activecampaign_abandoned')
            ->addBreadcrumb((string)__('ActiveCampaign'), (string)__('ActiveCampaign'))
            ->addBreadcrumb((string)__('Abandoned Carts'), (string)__('Abandoned Carts'));

        return $resultPage;
    }
}
