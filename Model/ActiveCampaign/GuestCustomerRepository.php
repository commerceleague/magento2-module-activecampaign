<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Model\ActiveCampaign;

use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Api\GuestCustomerRepositoryInterface;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\ActiveCampaign\GuestCustomer as GuestCustomerResource;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class GuestCustomerRepository
 */
class GuestCustomerRepository implements GuestCustomerRepositoryInterface
{

    /**
     * @var GuestCustomerResource
     */
    private $guestCustomerResource;

    /**
     * @var GuestCustomerFactory
     */
    private $guestCustomerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $magentoCustomerRepository;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param GuestCustomerResource       $customerResource
     * @param GuestCustomerFactory        $GuestCustomerFactory
     * @param CustomerRepositoryInterface $magentoCustomerRepository
     * @param CustomerRepository          $customerRepository
     */
    public function __construct(
        GuestCustomerResource $customerResource,
        GuestCustomerFactory $GuestCustomerFactory,
        CustomerRepositoryInterface $magentoCustomerRepository,
        CustomerRepository $customerRepository
    ) {
        $this->guestCustomerResource     = $customerResource;
        $this->guestCustomerFactory      = $GuestCustomerFactory;
        $this->magentoCustomerRepository = $magentoCustomerRepository;
        $this->customerRepository        = $customerRepository;
    }

    /**
     * @param Data\GuestCustomerInterface|AbstractModel $customer
     *
     * @return Data\GuestCustomerInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\GuestCustomerInterface $customer): Data\GuestCustomerInterface
    {
        try {
            $this->guestCustomerResource->save($customer);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getById($entityId): Data\GuestCustomerInterface
    {
        /** @var GuestCustomer $customer */
        $customer = $this->guestCustomerFactory->create();
        $this->guestCustomerResource->load($customer, $entityId);

        return $customer;
    }

    /**
     * @param Data\GuestCustomerInterface|AbstractModel $customer
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\GuestCustomerInterface $customer): bool
    {
        try {
            $this->guestCustomerResource->delete($customer);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($entityId): bool
    {
        $customer = $this->getById($entityId);

        if (!$customer->getId()) {
            throw new NoSuchEntityException(
                __('The Guest Customer with the "%1" ID doesn\'t exist', $entityId)
            );
        }

        return $this->delete($customer);
    }

    /**
     * @param string $email
     *
     * @return Data\GuestCustomerInterface
     */
    public function getByEmail(string $email): Data\GuestCustomerInterface
    {
        /** @var GuestCustomer $customer */
        $customer = $this->guestCustomerFactory->create();
        $this->guestCustomerResource->load($customer, $email, Data\GuestCustomerInterface::EMAIL);

        return $customer;
    }

    /**
     * @param string $email
     *
     * @return Data\GuestCustomerInterface
     */
    public function getOrCreate(array $customerData): Data\GuestCustomerInterface
    {

        if (array_key_exists(Data\GuestCustomerInterface::EMAIL, $customerData)) {

            $customerEmail = $customerData[Data\GuestCustomerInterface::EMAIL];
            $firstname     = $customerData[Data\GuestCustomerInterface::FIRSTNAME];
            $lastname      = $customerData[Data\GuestCustomerInterface::LASTNAME];

            /** @var Data\GuestCustomerInterface $guestCustomer */
            $guestCustomer = $this->getByEmail($customerEmail);

            try {
                $magentoCustomer = $this->magentoCustomerRepository->get($customerEmail);
                if ($magentoCustomer->getId()) {
                    $guestCustomer->setEmail($customerEmail)
                        ->setFirstname($firstname)
                        ->setLastname($lastname);

                    // set the activecampaign_id if set in activecampaign_customer
                    $activeCampaignCustomer = $this->customerRepository->getByMagentoCustomerId(
                        $magentoCustomer->getId()
                    );
                    if ($activeCampaignCustomer->getActiveCampaignId()) {
                        $guestCustomer->setActiveCampaignId($activeCampaignCustomer->getActiveCampaignId());
                    }
                }
            } catch (NoSuchEntityException $e) {

            }

            if (!$guestCustomer->getId()) {
                $guestCustomer->setEmail($customerEmail)
                    ->setFirstname($firstname)
                    ->setLastname($lastname);
            }

            $this->save($guestCustomer);

            return $guestCustomer;
        }
    }
}
