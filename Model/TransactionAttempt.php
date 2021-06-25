<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Model\AbstractModel;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;

/**
 * Class TransactionAttempt
 * @package Mygento\Kkm\Model
 */
class TransactionAttempt extends AbstractModel implements TransactionAttemptInterface
{
    /**
     * Get id
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get order id
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set order id
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get txn type
     * @return string|null
     */
    public function getTxnType()
    {
        return $this->getData(self::TXN_TYPE);
    }

    /**
     * Set txn type
     * @param string $txnType
     * @return $this
     */
    public function setTxnType($txnType)
    {
        return $this->setData(self::TXN_TYPE, $txnType);
    }

    /**
     * Get operation
     * @return int|null
     */
    public function getOperation()
    {
        return $this->getData(self::OPERATION);
    }

    /**
     * Set operation
     * @param int $operation
     * @return $this
     */
    public function setOperation($operation)
    {
        return $this->setData(self::OPERATION, $operation);
    }

    /**
     * Get sales entity id
     * @return int|null
     */
    public function getSalesEntityId()
    {
        return $this->getData(self::SALES_ENTITY_ID);
    }

    /**
     * Set sales entity id
     * @param int $salesEntityId
     * @return $this
     */
    public function setSalesEntityId($salesEntityId)
    {
        return $this->setData(self::SALES_ENTITY_ID, $salesEntityId);
    }

    /**
     * Get sales entity increment id
     * @return string|null
     */
    public function getSalesEntityIncrementId()
    {
        return $this->getData(self::SALES_ENTITY_INCREMENT_ID);
    }

    /**
     * Set sales entity increment id
     * @param string $salesEntityIncrementId
     * @return $this
     */
    public function setSalesEntityIncrementId($salesEntityIncrementId)
    {
        return $this->setData(self::SALES_ENTITY_INCREMENT_ID, $salesEntityIncrementId);
    }

    /**
     * Get status
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get message
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set message
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get number of trials
     * @return int|null
     */
    public function getNumberOfTrials()
    {
        return $this->getData(self::NUMBER_OF_TRIALS);
    }

    /**
     * Set number of trials
     * @param int $numberOfTrials
     * @return $this
     */
    public function setNumberOfTrials($numberOfTrials)
    {
        return $this->setData(self::NUMBER_OF_TRIALS, $numberOfTrials);
    }

    /**
     * Get total number of trials
     * @return int|null
     */
    public function getTotalNumberOfTrials()
    {
        return $this->getData(self::TOTAL_NUMBER_OF_TRIALS);
    }

    /**
     * Set total number of trials
     * @param int $totalNumberOfTrials
     * @return $this
     */
    public function setTotalNumberOfTrials($totalNumberOfTrials)
    {
        return $this->setData(self::TOTAL_NUMBER_OF_TRIALS, $totalNumberOfTrials);
    }

    /**
     * Get created at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated at
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get scheduled at
     * @return string|null
     */
    public function getScheduledAt()
    {
        return $this->getData(self::SCHEDULED_AT);
    }

    /**
     * Set scheduled at
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt($scheduledAt)
    {
        return $this->setData(self::SCHEDULED_AT, $scheduledAt);
    }

    /**
     * Get is scheduled
     * @return bool|null
     */
    public function getIsScheduled()
    {
        return $this->getData(self::IS_SCHEDULED);
    }

    /**
     * Set is scheduled
     * @param bool $isScheduled
     * @return $this
     */
    public function setIsScheduled($isScheduled)
    {
        return $this->setData(self::IS_SCHEDULED, $isScheduled);
    }

    /**
     * Get request json
     * @return string|null
     */
    public function getRequestJson()
    {
        return $this->getData(self::REQUEST_JSON);
    }

    /**
     * Set request json
     * @param string $requestJson
     * @return $this
     */
    public function setRequestJson($requestJson)
    {
        return $this->setData(self::REQUEST_JSON, $requestJson);
    }

    /**
     * @inheritDoc
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * Get error code
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * Set error code
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }

    /**
     * Get error type
     * @return string|null
     */
    public function getErrorType()
    {
        return $this->getData(self::ERROR_TYPE);
    }

    /**
     * Set error type
     * @param string $errorType
     * @return $this
     */
    public function setErrorType($errorType)
    {
        return $this->setData(self::ERROR_TYPE, $errorType);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Mygento\Kkm\Model\ResourceModel\TransactionAttempt::class);
    }
}
