<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPage;

/**
 * Class AbstractCustomer
 */
abstract class AbstractCustomer extends Action
{
    final public const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_customer';

    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::activecampaign_customer')
            ->addBreadcrumb((string)__('ActiveCampaign'), (string)__('ActiveCampaign'))
            ->addBreadcrumb((string)__('Customers'), (string)__('Customers'));

        return $resultPage;
    }
}
