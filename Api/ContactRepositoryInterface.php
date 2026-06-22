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
     * @throws CouldNotSaveException
     */
    public function save(Data\ContactInterface $contact): Data\ContactInterface;

    /**
     * @param int $entityId
     */
    public function getById($entityId): Data\ContactInterface;

    /**
     * @param string $email
     */
    public function getByEmail($email): Data\ContactInterface;

    /**
     * @param string $email
     * @throws CouldNotSaveException
     */
    public function getOrCreateByEmail($email): Data\ContactInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ContactInterface $contact): bool;

    /**
     * @param int $entityId
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId): bool;
}
