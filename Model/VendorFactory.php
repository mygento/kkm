<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Exception\InvalidArgumentException;
use Mygento\Kkm\Model\Atol\VendorFactory as AtolVendorFactory;
use Mygento\Kkm\Model\CheckOnline\VendorFactory as CheckOnlineVendorFactory;

class VendorFactory
{
    /**
     * @var \Mygento\Kkm\Model\Atol\VendorFactory
     */
    private $atolFactory;

    /**
     * @var \Mygento\Kkm\Model\CheckOnline\VendorFactory
     */
    private $checkOnlineFactory;

    public function __construct(
        AtolVendorFactory $atolFactory,
        CheckOnlineVendorFactory $checkOnlineFactory
    ) {
        $this->atolFactory = $atolFactory;
        $this->checkOnlineFactory = $checkOnlineFactory;
    }

    /**
     * @param string $vendorCode
     * @return \Mygento\Kkm\Model\VendorInterface
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function create($vendorCode)
    {
        switch ($vendorCode) {
            case \Mygento\Kkm\Model\Source\Vendors::ATOL_VENDOR_CODE:
                return $this->atolFactory->create();
            case \Mygento\Kkm\Model\Source\Vendors::CHECKONLINE_VENDOR_CODE:
                return $this->checkOnlineFactory->create();
        }

        throw new InvalidArgumentException(__('No such Kkm vendor: %1', $vendorCode));
    }
}
