<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface TransactionAttemptInterface
{
    public const ID = 'id';
    public const ORDER_ID = 'order_id';
    public const STORE_ID = 'store_id';
    public const TXN_TYPE = 'txn_type';
    public const OPERATION = 'operation';
    public const SALES_ENTITY_ID = 'sales_entity_id';
    public const SALES_ENTITY_INCREMENT_ID = 'sales_entity_increment_id';
    public const STATUS = 'status';
    public const MESSAGE = 'message';
    public const NUMBER_OF_TRIALS = 'number_of_trials';
    public const TOTAL_NUMBER_OF_TRIALS = 'total_number_of_trials';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const SCHEDULED_AT = 'scheduled_at';
    public const IS_SCHEDULED = 'is_scheduled';
    public const REQUEST_JSON = 'request_json';
    public const PARENT_ID = 'parent_id';
    public const ERROR_CODE = 'error_code';
    public const ERROR_TYPE = 'error_type';

    const OPERATION_LABEL = [
        1 => 'Payment',
        2 => 'Refund',
    ];

    const STATUS_NEW = 1;
    const STATUS_NEW_LABEL = 'New Attempt';
    const STATUS_SENT = 2;
    const STATUS_SENT_LABEL = 'Sent Attempt';
    const STATUS_ERROR = 3;
    const STATUS_ERROR_LABEL = 'Error Attempt';
    const STATUS_DONE = 4;
    const STATUS_DONE_LABEL = 'Done Attempt';

    /**
     * Get id
     * @return int|null
     */
    public function getId();

    /**
     * Set id
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get order id
     * @return int|null
     */
    public function getOrderId();

    /**
     * Set order id
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get store id
     * @return int|string|null
     */
    public function getStoreId();

    /**
     * Set order id
     * @param int|string|null $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get txn type
     * @return string|null
     */
    public function getTxnType();

    /**
     * Set txn type
     * @param string $txnType
     * @return $this
     */
    public function setTxnType($txnType);

    /**
     * Get operation
     * @return int|null
     */
    public function getOperation();

    /**
     * Set operation
     * @param int $operation
     * @return $this
     */
    public function setOperation($operation);

    /**
     * Get sales entity id
     * @return int|null
     */
    public function getSalesEntityId();

    /**
     * Set sales entity id
     * @param int $salesEntityId
     * @return $this
     */
    public function setSalesEntityId($salesEntityId);

    /**
     * Get sales entity increment id
     * @return string|null
     */
    public function getSalesEntityIncrementId();

    /**
     * Set sales entity increment id
     * @param string $salesEntityIncrementId
     * @return $this
     */
    public function setSalesEntityIncrementId($salesEntityIncrementId);

    /**
     * Get status
     * @return int|null
     */
    public function getStatus();

    /**
     * Set status
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get message
     * @return string|null
     */
    public function getMessage();

    /**
     * Set message
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get number of trials
     * @return int|null
     */
    public function getNumberOfTrials();

    /**
     * Set number of trials
     * @param int $numberOfTrials
     * @return $this
     */
    public function setNumberOfTrials($numberOfTrials);

    /**
     * Get total number of trials
     * @return int|null
     */
    public function getTotalNumberOfTrials();

    /**
     * Set total number of trials
     * @param int $totalNumberOfTrials
     * @return $this
     */
    public function setTotalNumberOfTrials($totalNumberOfTrials);

    /**
     * Get created at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get scheduled at
     * @return string|null
     */
    public function getScheduledAt();

    /**
     * Set scheduled at
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt($scheduledAt);

    /**
     * Get is scheduled
     * @return bool|null
     */
    public function getIsScheduled();

    /**
     * Set is scheduled
     * @param bool $isScheduled
     * @return $this
     */
    public function setIsScheduled($isScheduled);

    /**
     * Get request json
     * @return string|null
     */
    public function getRequestJson();

    /**
     * Set request json
     * @param string $requestJson
     * @return $this
     */
    public function setRequestJson($requestJson);

    /**
     * Get parent id
     * @return int|null
     */
    public function getParentId();

    /**
     * Set parent id
     * @param int $id
     * @return $this
     */
    public function setParentId($id);

    /**
     * Get error code
     * @return string|null
     */
    public function getErrorCode();

    /**
     * Set error code
     * @param string|null $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode);

    /**
     * Get error type
     * @return string|null
     */
    public function getErrorType();

    /**
     * Set error type
     * @param string|null $errorType
     * @return $this
     */
    public function setErrorType($errorType);
}
