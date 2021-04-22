<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
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
     * @return string|int
     */
    public function getEntityStoreId();

    /**
     * @param string|int $storeId
     * @return $this
     */
    public function setEntityStoreId($storeId);
}
