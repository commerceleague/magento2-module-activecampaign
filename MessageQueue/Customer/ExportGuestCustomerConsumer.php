<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\MessageQueue\Customer;

use CommerceLeague\ActiveCampaign\Api\Data\GuestCustomerInterface;
use CommerceLeague\ActiveCampaign\Api\GuestCustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Gateway\Client;
use CommerceLeague\ActiveCampaign\Gateway\Request\CustomerBuilder as CustomerRequestBuilder;
use CommerceLeague\ActiveCampaign\Logger\Logger;
use CommerceLeague\ActiveCampaign\MessageQueue\AbstractConsumer;
use CommerceLeague\ActiveCampaign\MessageQueue\ConsumerInterface;
use CommerceLeague\ActiveCampaignApi\Exception\HttpException;
use CommerceLeague\ActiveCampaignApi\Exception\UnprocessableEntityHttpException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class ExportCustomerConsumer
 */
class ExportGuestCustomerConsumer extends AbstractConsumer implements ConsumerInterface
{

    /**
     * @var GuestCustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerRequestBuilder
     */
    private $customerRequestBuilder;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Logger                           $logger
     * @param GuestCustomerRepositoryInterface $customerRepository
     * @param CustomerRequestBuilder           $customerRequestBuilder
     * @param Client                           $client
     */
    public function __construct(
        Logger $logger,
        GuestCustomerRepositoryInterface $customerRepository,
        CustomerRequestBuilder $customerRequestBuilder,
        Client $client
    ) {
        parent::__construct($logger);
        $this->customerRepository     = $customerRepository;
        $this->customerRequestBuilder = $customerRequestBuilder;
        $this->client                 = $client;
    }

    /**
     * @param string $message
     *
     * @throws CouldNotSaveException
     */
    public function consume(string $message): void
    {
        $message = json_decode($message, true);

        $customerData = $message['customer_data'];

        $guestCustomer = $this->customerRepository->getOrCreate($customerData);
        $request       = $this->customerRequestBuilder->buildWithGuest($guestCustomer);

        try {
            $apiResponse = $this->performApiRequest($guestCustomer, $request);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logUnprocessableEntityHttpException($e, $request);
            return;
        } catch (HttpException $e) {
            $this->logException($e);
            return;
        }

        $guestCustomer->setActiveCampaignId($apiResponse['ecomCustomer']['id']);
        $this->customerRepository->save($guestCustomer);
    }

    /**
     * @param GuestCustomerInterface $customer
     * @param array                  $request
     *
     * @return array
     * @throws HttpException
     */
    private function performApiRequest(GuestCustomerInterface $customer, array $request): array
    {
        if ($activeCampaignId = $customer->getActiveCampaignId()) {
            return $this->client->getCustomerApi()->update((int)$activeCampaignId, ['ecomCustomer' => $request]);
        } else {
            return $this->client->getCustomerApi()->create(['ecomCustomer' => $request]);
        }
    }
}
