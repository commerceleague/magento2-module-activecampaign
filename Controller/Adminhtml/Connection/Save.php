<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Connection;

use CommerceLeague\ActiveCampaign\Api\ConnectionRepositoryInterface;
use CommerceLeague\ActiveCampaign\Controller\Adminhtml\AbstractConnection;
use CommerceLeague\ActiveCampaign\Model\Connection;
use CommerceLeague\ActiveCampaign\Model\ConnectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Save
 */
class Save extends AbstractConnection implements HttpPostActionInterface
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
     * @param Action\Context $context
     * @param ConnectionFactory $connectionFactory
     * @param ConnectionRepositoryInterface $connectionRepository
     */
    public function __construct(
        Action\Context $context,
        ConnectionFactory $connectionFactory,
        ConnectionRepositoryInterface $connectionRepository
    ) {
        $this->connectionFactory = $connectionFactory;
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
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            if (empty($data['connection_id'])) {
                $data['connection_id'] = null;
            }

            /** @var Connection $model */
            $model = $this->connectionFactory->create();

            $id = $this->getRequest()->getParam('connection_id');

            if ($id) {
                try {
                    $model = $this->connectionRepository->getById($id);
                } catch (NoSuchEntityException $e) {
                    $this->messageManager->addErrorMessage(__('This Connection no longer exists'));
                    return $resultRedirect->setPath('*/*');
                }
            }

            $model->setData($data);

            try {
                $this->connectionRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the Connection.'));
                return $this->prepareResultRedirect($model, $data, $resultRedirect);
            } catch (CouldNotSaveException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Connection.'));
            }

            return $resultRedirect->setPath('*/*/edit', ['connection_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param Connection $model
     * @param array $data
     * @param ResultInterface|ResultRedirect $resultRedirect
     * @return ResultInterface
     */
    private function prepareResultRedirect(
        Connection $model,
        array $data,
        ResultInterface $resultRedirect
    ): ResultInterface {
        $redirect = $data['back'] ?? 'close';

        if ($redirect ==='continue') {
            $resultRedirect->setPath('*/*/edit', ['connection_id' => $model->getId()]);
        } else if ($redirect === 'close') {
            $resultRedirect->setPath('*/*/');
        }

        return $resultRedirect;
    }
}
