<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Exception\InvalidArgumentException;

class VendorFactory
{
    /**
     * @var array
     */
    private $vendorFactories;

    public function __construct(
        $vendorFactories = []
    ) {
        $this->vendorFactories = $vendorFactories;
    }

    /**
     * @param string $vendorCode
     * @return \Mygento\Kkm\Model\VendorInterface
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function create($vendorCode)
    {
        if (!isset($this->vendorFactories[$vendorCode])) {
            throw new InvalidArgumentException(__('No such Kkm vendor: %1', $vendorCode));
        }

        return $this->vendorFactories[$vendorCode]->create();
    }
}
