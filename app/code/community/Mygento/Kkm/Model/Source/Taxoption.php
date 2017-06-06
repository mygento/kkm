<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Taxoption
{

    /**
     * Options source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => Mage::helper('kkm')->__('Without VAT')
            ],
            [
                'value' => 'vat0',
                'label' => Mage::helper('kkm')->__('vat0')
            ],
            [
                'value' => 'vat10',
                'label' => Mage::helper('kkm')->__('vat10')
            ],
            [
                'value' => 'vat18',
                'label' => Mage::helper('kkm')->__('vat18')
            ],
            [
                'value' => 'vat110',
                'label' => Mage::helper('kkm')->__('vat110')
            ],
            [
                'value' => 'vat118',
                'label' => Mage::helper('kkm')->__('vat118')
            ]
        ];
    }
}
