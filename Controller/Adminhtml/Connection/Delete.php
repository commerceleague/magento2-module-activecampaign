<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Connection;

use CommerceLeague\ActiveCampaign\Api\ConnectionRepositoryInterface;
use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractConnection;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Delete
 */
class Delete extends AbstractConnection implements HttpPostActionInterface
{
    /**
     * @var ConnectionRepositoryInterface
     */
    private $connectionRepository;

    /**
     * @param Action\Context $context
     * @param ConnectionRepositoryInterface $connectionRepository
     */
    public function __construct(
        Action\Context $context,
        ConnectionRepositoryInterface $connectionRepository
    ) {
        $this->connectionRepository = $connectionRepository;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam('connection_id');

        if ($id) {
            try {
                $this->connectionRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('You deleted the connection.'));
            } catch (NoSuchEntityException|CouldNotDeleteException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['connection_id' => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while deleting the Connection.')
                );
                return $resultRedirect->setPath('*/*/edit', ['connection_id' => $id]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
