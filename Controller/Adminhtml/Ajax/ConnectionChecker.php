<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller\Adminhtml\Ajax;

use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Helper\Config;
use CommerceLeague\ActiveCampaignApi\Exception\ClientErrorHttpException;
use CommerceLeague\ActiveCampaignApi\Exception\NotFoundHttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnauthorizedHttpException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class ConnectionChecker extends Action
{

    const ADMIN_RESOURCE = 'CommerceLeague_ActiveCampaign::activecampaign_config_connectionChecker';

    private Config      $configHelper;

    private JsonFactory $resultJsonFactory;

    private Client      $client;

    public function __construct(
        Context     $context,
        Client      $client,
        Config      $configHelper,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->client            = $client;
        $this->configHelper      = $configHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if ($this->getRequest()->isAjax()) {
            $message = sprintf(__('Connection established to connectionId %s'), $this->configHelper->getConnectionId());
            try {
                $this->client->getConnectionApi()->get((int)$this->configHelper->getConnectionId());
                $response = $result->setData(['message' => $message]);
            } catch (UnauthorizedHttpException $exception) {
                $response = $this->handleException($result, 403, $exception->getMessage());
            } catch (ClientErrorHttpException $exception) {
                $response = $this->handleException($result, 404, $exception->getMessage());
            } catch (NotFoundHttpException $exception) {
                $response = $this->handleException($result, 404, $exception->getMessage());
            }
            return $response;
        }
    }

    private function handleException(Json $result, int $httpCode, string $message): Json
    {
        $result->setStatusHeader($httpCode, null, $message);

        return $result->setData(['message' => $message]);
    }
}
