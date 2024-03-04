<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Controller;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\Controller\Result\Raw as RawResult;
use Magento\Framework\Phrase;

/**
 * Class AbstractWebhook
 */
abstract class AbstractWebhook extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const PARAM_TOKEN = 'token';

    public function __construct(
        Context $context,
        private readonly ConfigHelper $configHelper,
        private readonly RawResultFactory $rawResultFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        /** @var RawResult $response */
        $response = $this->rawResultFactory->create();
        $response->setHttpResponseCode(401);
        $response->setContents('');

        return new InvalidRequestException($response, [new Phrase('Invalid Token.')]);
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $token = $request->getParam(self::PARAM_TOKEN);

        if (!$token || $this->configHelper->getWebhookToken() !== $token) {
            return false;
        }

        return true;
    }
}
