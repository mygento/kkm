<?php


namespace Mygento\Kkm\Api\Queue;


interface QueueMessageInterface
{
    /**
     * @return string|int
     */
    public function getEntityId();

    /**
     * @param string|int $entityId
     * @return $this
     */
    public function setEntityId($entityId): QueueMessageInterface;

    /**
     * @return string|int
     */
    public function getEntityStoreId();

    /**
     * @param string|int $storeId
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
