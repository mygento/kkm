<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Vendors implements \Magento\Framework\Data\OptionSourceInterface
{
    public const ATOL_VENDOR_CODE = 'atol';
    public const CHECKONLINE_VENDOR_CODE = 'checkonline';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::ATOL_VENDOR_CODE => __('Atol'),
            self::CHECKONLINE_VENDOR_CODE => __('Checkonline'),
        ];
    }
}
