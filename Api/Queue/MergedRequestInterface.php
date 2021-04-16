<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

use Mygento\Kkm\Api\Data\RequestInterface;

interface MergedRequestInterface
{
    /**
     * @return string[]
     */
    public function getRequests();

    /**
     * @param string[] $value
     * @return $this
     */
    public function setRequests(array $value);
}
