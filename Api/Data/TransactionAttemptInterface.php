<?php

namespace Mygento\Kkm\Api\Data;

interface TransactionAttemptInterface
{
    const ID = 'id';
    const ORDER_ID = 'order_id';
    const OPERATION = 'operation';
    const SALES_ENTITY_ID = 'sales_entity_id';
    const STATUS = 'status';
    const MESSAGE = 'message';
    const NUMBER_OF_TRIALS = 'number_of_trials';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

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
}
