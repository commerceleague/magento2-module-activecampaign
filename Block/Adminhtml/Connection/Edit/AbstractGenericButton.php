<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Block\Adminhtml\Connection\Edit;

use CommerceLeague\ActiveCampaign\Api\ConnectionRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class AbstractGenericButton
 */
abstract class AbstractGenericButton
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var ConnectionRepositoryInterface
     */
    private $connectionRepository;

    /**
     * @param Context $context
     * @param ConnectionRepositoryInterface $connectionRepository
     */
    public function __construct(
        Context $context,
        ConnectionRepositoryInterface $connectionRepository
    ) {
        $this->context = $context;
        $this->connectionRepository = $connectionRepository;
    }

    /**
     * @return int|null
     */
    protected function getConnectionId(): ?int
    {
        try {
            return (int)$this->connectionRepository->getById(
                $this->context->getRequest()->getParam('connection_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
            // noop
        }

        return null;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
