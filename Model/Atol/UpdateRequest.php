<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\DataObject;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

class UpdateRequest extends DataObject implements UpdateRequestInterface
{
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
    public function getEntityStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }
}
