<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\DataObject;

class UpdateRequest extends DataObject implements \Mygento\Kkm\Api\Data\UpdateRequestInterface
{
    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->getUuid();
    }

    /**
     * Get uuid
     * @return string|null
     */
    public function getUuid()
    {
        return $this->getData(self::UUID);
    }

    /**
     * Set uuid
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid)
    {
        return $this->setData(self::UUID, $uuid);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): ?int
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId($id): \Mygento\Kkm\Api\Data\UpdateRequestInterface
    {
        return $this->setData(self::STORE_ID, $id);
    }
}
