<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\CustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data\CustomerInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Customer\Api\CustomerRepositoryInterface as MagentoCustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ExportCustomerConsumer
 */
class ExportCustomerConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @param MagentoCustomerRepositoryInterface $magentoCustomerRepository
     * @param Logger                             $logger
     * @param CustomerRepositoryInterface        $customerRepository
     * @param CustomerRequestBuilder             $customerRequestBuilder
     * @param Client                             $client
     */
    public function __construct(
        private readonly MagentoCustomerRepositoryInterface $magentoCustomerRepository,
        Logger $logger,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerRequestBuilder $customerRequestBuilder,
        private readonly Client $client
    ) {
        parent::__construct($logger);
    }

    /**
     * @param string $message
     *
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        try {
            $magentoCustomer = $this->magentoCustomerRepository->getById($message['magento_customer_id']);
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->getLogger()->error($e);
            return;
        }

        $customer = $this->customerRepository->getOrCreateByMagentoCustomerId($magentoCustomer->getId());
        $request  = $this->customerRequestBuilder->build($magentoCustomer);

        try {
            $apiResponse                  = $this->performApiRequest($customer, $request);
            $activeCampaignEcomCustomerId = $apiResponse['ecomCustomer']['id'];
        } catch (UnprocessableEntityHttpException $e) {
            $activeCampaignEcomCustomerId = $this->logUnprocessableEntityHttpException($e, $request);
            if ($activeCampaignEcomCustomerId === null) {
                return;
            }

        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }

        $customer->setActiveCampaignId($activeCampaignEcomCustomerId);
        $this->customerRepository->save($customer);
    }

    /**
     * override the default logging to update the entry in database
     *
     * @param UnprocessableEntityHttpException $unprocessableEntityHttpException
     * @param                                  $request
     *
     * @return mixed|void|null
     */
    public function logUnprocessableEntityHttpException(
        UnprocessableEntityHttpException $unprocessableEntityHttpException, array $request
    ): mixed {

        $activeCampaignEcommerceId = null;
        $errors                    = $unprocessableEntityHttpException->getResponseErrors();
        if ($errors !== []) {
            foreach ($errors as $error) {
                if (isset($error['code']) && $error['code'] == 'duplicate') {
                    $filters = [
                        'filters' => [
                            'email'        => $request['email'],
                            'connectionid' => $request['connectionid']
                        ]
                    ];
                    $this->getLogger()->info(print_r($filters, true));
                    $response = $this->client->getCustomerApi()->listPerPage(1, 0, $filters);
                    $items    = $response->getItems();
                    $customer = $items[0];
                    $this->getLogger()->info(print_r($customer, true));
                    if (strtolower((string) $customer['email']) === strtolower((string) $request['email'])) {
                        $activeCampaignEcommerceId = $customer['id'];
                    }
                }
            }
        }
        if (null === $activeCampaignEcommerceId) {
            parent::logUnprocessableEntityHttpException(
                $unprocessableEntityHttpException, $request
            );
        }
        return $activeCampaignEcommerceId;
    }

    /**
     * @return array
     * @throws HttpException
     */
    private function performApiRequest(CustomerInterface $customer, array $request): array
    {
        if ($activeCampaignId = $customer->getActiveCampaignId()) {
            return $this->client->getCustomerApi()->update((int)$activeCampaignId, ['ecomCustomer' => $request]);
        } else {
            return $this->client->getCustomerApi()->create(['ecomCustomer' => $request]);
        }
    }

    /**
     * @inheritDoc
     */
    function processDuplicateEntity(array $request, string $key): void
    {
        return;
    }
}
