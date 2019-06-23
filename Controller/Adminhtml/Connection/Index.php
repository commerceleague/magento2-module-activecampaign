<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Connection;

use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractConnection;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;
use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Class Index
 */
class Index extends AbstractConnection
{
    /**
     * @var ResultPageFactory
     */
    private $resultPageFactory;

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
        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('Connections'));

        return $resultPage;
    }
}
