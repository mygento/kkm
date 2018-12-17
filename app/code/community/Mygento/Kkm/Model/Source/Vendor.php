<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Vendor
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
                'value' => '0',
                'label' => Mage::helper('adminhtml')->__('-- Please select --')
            ],
            [
                'value' => 'atol3',
                'label' => Mage::helper('kkm')->__('Atol v.3')
            ],
            [
                'value' => 'atol4',
                'label' => Mage::helper('kkm')->__('Atol v.4')
            ]
        ];
    }
}
