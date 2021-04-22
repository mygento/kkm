<?php


namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Queue\QueueMessageInterface;

class QueueMessage implements QueueMessageInterface
{
    /**
     * @var string|int
     */
    private $entityId;

    /**
     * @var string|int
     */
    private $entityStoreId;

    /**
     * @var int
     */
    private $operationType;

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId): QueueMessageInterface
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEntityStoreId()
    {
        return $this->entityStoreId;
    }

    /**
     * @inheritDoc
     */
    public function setEntityStoreId($storeId): QueueMessageInterface
    {
        $this->entityStoreId = $storeId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOperationType(): int
    {
        return $this->operationType;
    }

    /**
     * @inheritDoc
     */
    public function setOperationType($operationType): QueueMessageInterface
    {
        $this->operationType = $operationType;

        return $this;
    }
}
