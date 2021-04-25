<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api;

interface TransactionAttemptRepositoryInterface
{
    /**
     * Save TransactionAttempt
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function save(Data\TransactionAttemptInterface $entity);

    /**
     * Retrieve TransactionAttempt
     * @param int $entityId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function getById($entityId);

    /**
     * Retrieve TransactionAttempt
     * @param int $operation
     * @param int $entityId
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function getByEntityId($operation, $entityId);

    /**
     * Retrieve TransactionAttempt which should be marked as parent for new one
     *
     * @param int $entityId
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function getParentAttempt($entityId): Data\TransactionAttemptInterface;

    /**
     * Retrieve TransactionAttempt
     * @param int $operation
     * @param int $orderId
     * @param string $entityIncrementId
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function getByIncrementId($operation, $orderId, $entityIncrementId);

    /**
     * Retrieve TransactionAttempt entities matching the specified criteria
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete TransactionAttempt
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool true on success
     */
    public function delete(Data\TransactionAttemptInterface $entity);

    /**
     * Delete TransactionAttempt
     * @param int $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool true on success
     */
    public function deleteById($entityId);
}
