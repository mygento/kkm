<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

use Mygento\Kkm\Api\Data\RequestInterface;

interface MergedRequestInterface
{
    /**
     * @return RequestInterface[]
     */
    public function getRequests();

    /**
     * @param RequestInterface[] $value
     * @return $this
     */
    public function setRequests(array $value);
}
