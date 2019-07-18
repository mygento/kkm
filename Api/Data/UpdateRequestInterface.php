<?php

namespace Mygento\Kkm\Api\Data;

interface UpdateRequestInterface
{
    const UUID = 'uuid';

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
