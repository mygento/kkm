<?php

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
     * @param string $entityIncrementId
     */
    public function getByEntityId($operation, $entityIncrementId);

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
