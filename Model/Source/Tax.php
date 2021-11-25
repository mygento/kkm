<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Tax implements \Magento\Framework\Option\ArrayInterface
{
    public const TAX_NONE = 'none';
    public const TAX_VAT0 = 'vat0';
    public const TAX_VAT10 = 'vat10';
    public const TAX_VAT20 = 'vat20';
    public const TAX_VAT110 = 'vat110';
    public const TAX_VAT120 = 'vat120';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TAX_NONE,
                'label' => __('Without VAT'),
            ],
            [
                'value' => self::TAX_VAT0,
                'label' => __('vat0'),
            ],
            [
                'value' => self::TAX_VAT10,
                'label' => __('vat10'),
            ],
            [
                'value' => self::TAX_VAT20,
                'label' => __('vat20'),
            ],
            [
                'value' => self::TAX_VAT110,
                'label' => __('vat110'),
            ],
            [
                'value' => self::TAX_VAT120,
                'label' => __('vat120'),
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getAllTaxes()
    {
        return [
            self::TAX_NONE,
            self::TAX_VAT0,
            self::TAX_VAT10,
            self::TAX_VAT20,
            self::TAX_VAT110,
            self::TAX_VAT120,
        ];
    }
}
