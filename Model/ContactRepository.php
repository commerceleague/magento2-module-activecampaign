<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Model;

use CommerceLeague\ActiveCampaign\Api\ContactRepositoryInterface;
use CommerceLeague\ActiveCampaign\Api\Data;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Contact as ContactResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class ContactRepository
 */
class ContactRepository implements ContactRepositoryInterface
{
    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @param ContactResource $contactResource
     * @param ContactFactory $contactFactory
     */
    public function __construct(
        ContactResource $contactResource,
        ContactFactory $contactFactory
    ) {
        $this->contactResource = $contactResource;
        $this->contactFactory = $contactFactory;
    }

    /**
     * @param Data\ContactInterface|AbstractModel $contact
     * @return Data\ContactInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\ContactInterface $contact): Data\ContactInterface
    {
        try {
            $this->contactResource->save($contact);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $contact;
    }

    /**
     * @inheritDoc
     */
    public function getById($contactId): Data\ContactInterface
    {
        /** @var Contact $contact */
        $contact = $this->contactFactory->create();
        $this->contactResource->load($contact, $contactId);

        if (!$contact->getId()) {
            throw new NoSuchEntityException(
                __('The Contact with the "%1" ID doesn\'t exist', $contactId)
            );
        }

        return $contact;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId($customerId): Data\ContactInterface
    {
        /** @var Contact $contact */
        $contact = $this->contactFactory->create();
        $this->contactResource->load($contact, $customerId, Data\ContactInterface::CUSTOMER_ID);

        if (!$contact->getId()) {
            throw new NoSuchEntityException(
                __('The Contact with the "%1" customer ID doesn\'t exist', $customerId)
            );
        }

        return $contact;
    }

    /**
     * @param Data\ContactInterface|AbstractModel $contact
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ContactInterface $contact): bool
    {
        try {
            $this->contactResource->delete($contact);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($contactId): bool
    {
        return $this->delete($this->getById($contactId));
    }
}
