<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Queue\MergedRequestInterface;

class MergedRequest implements MergedRequestInterface
{
    /**
     * @var RequestInterface[]
     */
    private $requests;

    /**
     * @inheritdoc
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * @inheritdoc
     */
    public function setRequests(array $value)
    {
        $this->requests = $value;

        return $this;
    }
}
