<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Magento\Framework\DataObject;

class MergedUpdateRequest extends DataObject implements \Mygento\Kkm\Api\Queue\MergedUpdateRequestInterface
{
    /**
     * Get requests
     * @return string|null
     */
    public function getRequests()
    {
        return $this->getData(self::REQUESTS);
    }

    /**
     * Set requests
     * @param string $requests
     * @return $this
     */
    public function setRequests($requests)
    {
        return $this->setData(self::REQUESTS, $requests);
    }
}
