<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Queue\QueueMessageInterface;

class QueueMessage implements QueueMessageInterface
{
    /**
     * @var int|string
     */
    private $entityId;

    /**
     * @var int|string
     */
    private $entityStoreId;

    /**
     * @var int
     */
    private $operationType;

    public function __toArray()
    {
        return [
            'entityId' => $this->getEntityId(),
            'entityStoreId' => $this->getEntityStoreId(),
            'operationType' => $this->getOperationType(),
        ];
    }

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
