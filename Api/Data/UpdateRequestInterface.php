<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface UpdateRequestInterface
{
    public const UPDATE_OPERATION_TYPE = 3;
    public const UUID = 'uuid';
    public const STORE_ID = 'store_id';

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
