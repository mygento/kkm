<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
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
}
