<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Connection;

use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractConnection;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory as ResultForwardFactory;
use Magento\Backend\Model\View\Result\ForwardFactory as ResultForward;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Class NewAction
 */
class NewAction extends AbstractConnection implements HttpGetActionInterface
{
    /**
     * @var ResultForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param Action\Context $context
     * @param ResultForwardFactory $resultForwardFactory
     */
    public function __construct(
        Action\Context $context,
        ResultForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultForward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
