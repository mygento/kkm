<?php

namespace Mygento\Kkm\Api\Queue;

interface MergedUpdateRequestInterface
{
    const REQUESTS = 'requests';

    /**
     * Get requests
     * @return \Mygento\Kkm\Api\Data\UpdateRequestInterface[]|null
     */
    public function getRequests();

    /**
     * Set requests
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface[] $requests
     * @return $this
     */
    public function setRequests($requests);
}
