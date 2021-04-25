<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface UpdateRequestInterface
{
    const UPDATE_OPERATION_TYPE = 3;

    const UUID = 'uuid';
    const ENTITY_STORE_ID = 'entity_store_id';

    /**
     * Get uuid
     * @return string|null
     */
    public function getUuid();

    /**
     * Set uuid
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * @return int|string
     */
    public function getEntityStoreId();

    /**
     * @param int|string $storeId
     * @return $this
     */
    public function setEntityStoreId($storeId);
}
