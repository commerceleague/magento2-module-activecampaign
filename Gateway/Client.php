<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Gateway;

use CommerceLeague\ActiveCampaign\Helper\Config as ConfigHelper;
use CommerceLeague\ActiveCampaignApi\Api\AbandonedCartApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\ConnectionApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\ContactApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\CustomerApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\ListsApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\OrderApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\Api\TagsApiResourceInterface;
use CommerceLeague\ActiveCampaignApi\ClientBuilder;
use CommerceLeague\ActiveCampaignApi\CommonClientInterface;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Http\Factory\Guzzle\RequestFactory as GuzzleRequestFactory;
use Http\Factory\Guzzle\StreamFactory as GuzzleStreamFactory;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Class Client
 */
class Client
{

    public function __construct(private readonly ConfigHelper $configHelper)
    {
    }

    /**
     * @return AbandonedCartApiResourceInterface
     * @throws InvalidArgumentException
     */
    public function getAbandonedCartApi(): AbandonedCartApiResourceInterface
    {
        return $this->getCommonClient()->getAbandonedCartApi();
    }

    /**
     * @return ConnectionApiResourceInterface
     */
    public function getConnectionApi(): ConnectionApiResourceInterface
    {
        return $this->getCommonClient()->getConnectionApi();
    }

    /**
     * @return ContactApiResourceInterface
     * @throws MissingCredentialsException
     */
    public function getContactApi(): ContactApiResourceInterface
    {
        return $this->getCommonClient()->getContactApi();
    }

    /**
     * @return CustomerApiResourceInterface
     */
    public function getCustomerApi(): CustomerApiResourceInterface
    {
        return $this->getCommonClient()->getCustomerApi();
    }

    /**
     * @return OrderApiResourceInterface
     * @throws InvalidArgumentException
     */
    public function getOrderApi(): OrderApiResourceInterface
    {
        return $this->getCommonClient()->getOrderApi();
    }

    /**
     * @return TagsApiResourceInterface
     * @throws InvalidArgumentException
     */
    public function getTagsApi(): TagsApiResourceInterface
    {
        return $this->getCommonClient()->getTagsApi();
    }

    /**
     * @return ListsApiResourceInterface
     * @throws InvalidArgumentException
     */
    public function getListsApi(): ListsApiResourceInterface
    {
        return $this->getCommonClient()->getListsApi();
    }

    /**
     * @return CommonClientInterface
     * @throws InvalidArgumentException
     */
    private function getCommonClient(): CommonClientInterface
    {
        $url   = $this->configHelper->getApiUrl();
        $token = $this->configHelper->getApiToken();

        if ($this->configHelper->isConnectionSet() === false) {
            throw new InvalidArgumentException(
                __('Connection Credentials are not set')
            );
        }

        $clientBuilder = new ClientBuilder();
        $clientBuilder->setHttpClient(new GuzzleClient());
        $clientBuilder->setRequestFactory(new GuzzleRequestFactory());
        $clientBuilder->setStreamFactory(new GuzzleStreamFactory());

        return $clientBuilder->buildCommonClient($url, $token);
    }
}
