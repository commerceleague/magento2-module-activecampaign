<?php
declare(strict_types=1);
/**
 */
namespace CommerceLeague\ActiveCampaign\Api;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;

/**
 * Interface ContactRepositoryInterface
 */
interface ContactRepositoryInterface
{
    /**
     * @return Data\ContactInterface
     * @throws CouldNotSaveException
     */
    public function save(Data\ContactInterface $contact): Data\ContactInterface;

    /**
     * @param int $entityId
     * @return Data\ContactInterface
     */
    public function getById($entityId): Data\ContactInterface;

    /**
     * @param string $email
     * @return Data\ContactInterface
     */
    public function getByEmail($email): Data\ContactInterface;

    /**
     * @param string $email
     * @return Data\ContactInterface
     * @throws CouldNotSaveException
     */
    public function getOrCreateByEmail($email): Data\ContactInterface;

    /**
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ContactInterface $contact): bool;

    /**
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
