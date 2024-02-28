<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page as ResultPage;

/**
 * Class AbstractContact
 */
abstract class AbstractContact extends Action
{
    final public const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_contact';

    /**
     * @return ResultPage
     */
    protected function initPage(ResultPage $resultPage): ResultPage
    {
        $resultPage->setActiveMenu('CommerceLeague_ActiveCampaign::activecampaign_contact')
            ->addBreadcrumb((string)__('ActiveCampaign'), (string)__('ActiveCampaign'))
            ->addBreadcrumb((string)__('Contacts'), (string)__('Contacts'));

        return $resultPage;
    }
}
