<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
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
                'value' => "0",
                'label' => Mage::helper('adminhtml')->__('-- Please select --')
            ],
            [
                'value' => "atol",
                'label' => Mage::helper('adminhtml')->__('Atol')
            ]
        ];
    }
}
