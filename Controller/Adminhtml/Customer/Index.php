<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Customer;

use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractCustomer;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use \Magento\Backend\Model\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

/**
 * Class Index
 */
class Index extends AbstractCustomer implements HttpGetActionInterface
{
    /**
     * @param Action\Context $context
     * @param ResultPageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        protected ResultPageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultPage $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $this->initPage($resultPage)->getConfig()->getTitle()->prepend((string)__('ActiveCampaign Customers'));

        return $resultPage;
    }
}
