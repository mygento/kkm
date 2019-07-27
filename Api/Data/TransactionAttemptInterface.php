<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface TransactionAttemptInterface
{
    const ID = 'id';
    const ORDER_ID = 'order_id';
    const OPERATION = 'operation';
    const SALES_ENTITY_ID = 'sales_entity_id';
    const SALES_ENTITY_INCREMENT_ID = 'sales_entity_increment_id';
    const STATUS = 'status';
    const MESSAGE = 'message';
    const NUMBER_OF_TRIALS = 'number_of_trials';
    const TOTAL_NUMBER_OF_TRIALS = 'total_number_of_trials';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const SCHEDULED_AT = 'scheduled_at';
    const IS_SCHEDULED = 'is_scheduled';
    const REQUEST_JSON = 'request_json';

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
}
