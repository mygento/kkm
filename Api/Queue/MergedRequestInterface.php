<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

interface MergedRequestInterface
{
    /**
     * @return \Mygento\Kkm\Api\Queue\QueueMessageInterface[]
     */
    public function getRequests();

    /**
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterface[] $value
     * @return $this
     */
    public function setRequests(array $value);
}
