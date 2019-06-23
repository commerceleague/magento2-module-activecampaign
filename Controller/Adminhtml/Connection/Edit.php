<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Connection;

use CommerceLeague\ActiveCampaign\Api\ConnectionRepositoryInterface;
use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractConnection;
use CommerceLeague\ActiveCampaign\Model\ConnectionFactory;
use CommerceLeague\ActiveCampaign\Model\Connection;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;
use Magento\Framework\View\Result\Page as ResultPage;

/**
 * Class Edit
 */
class Edit extends AbstractConnection implements HttpGetActionInterface
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var ConnectionRepositoryInterface
     */
    private $connectionRepository;

    /**
     * @var ResultPageFactory
     */
    private $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param ConnectionFactory $connectionFactory
     * @param ConnectionRepositoryInterface $connectionRepository
     * @param ResultPageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        ConnectionFactory $connectionFactory,
        ConnectionRepositoryInterface $connectionRepository,
        ResultPageFactory $resultPageFactory
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->connectionRepository = $connectionRepository;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('connection_id');

        /** @var Connection $model */
        $model = $this->connectionFactory->create();

        if ($id) {
            try {
                $model = $this->connectionRepository->getById($id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This Connection no longer exists.'));

                /** @var ResultRedirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        /** @var ResultPage $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Connection') : __('New Connection'),
            $id ? __('Edit Connection') : __('New Connection')
        );

        $resultPage->getConfig()->getTitle()->prepend(__('Connection'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getName() : __('New Connection'));

        return $resultPage;
    }
}
