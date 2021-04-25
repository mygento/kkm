<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

interface QueueMessageInterface
{
    /**
     * @return int|string
     */
    public function getEntityId();

    /**
     * @param int|string $entityId
     * @return $this
     */
    public function setEntityId($entityId): QueueMessageInterface;

    /**
     * @return int|string
     */
    public function getEntityStoreId();

    /**
     * @param int|string $storeId
     * @return $this
     */
    public function setEntityStoreId($storeId): QueueMessageInterface;

    /**
     * @return int
     */
    public function getOperationType(): int;

    /**
     * @param int $operationType
     * @return QueueMessageInterface
     */
    public function setOperationType($operationType): QueueMessageInterface;
}
