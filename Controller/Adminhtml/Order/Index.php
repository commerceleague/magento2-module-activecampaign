<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Order;

use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractOrder;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

class Index extends AbstractOrder implements HttpGetActionInterface
{
    /**
     * @var ResultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param ResultPageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        ResultPageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultPage $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('ActiveCampaign Orders'));

        return $resultPage;
    }
}
