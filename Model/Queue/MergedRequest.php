<?php

namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Queue\MergedRequestInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class MergedRequest implements MergedRequestInterface
{
    /**
     * @var RequestInterface[]
     */
    private $requests;

    /**
     * {@inheritdoc}
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequests(array $value)
    {
        $this->requests = $value;

        return $this;
    }
}
