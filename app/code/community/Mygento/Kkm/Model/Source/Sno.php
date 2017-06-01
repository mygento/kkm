<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Sno
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
                'value' => "osn",
                'label' => Mage::helper('kkm')->__('общая СН')
            ],
            [
                'value' => "usn_income",
                'label' => Mage::helper('kkm')->__('упрощенная СН (доходы)')
            ],
            [
                'value' => "usn_income_outcome",
                'label' => Mage::helper('kkm')->__('упрощенная СН (доходы минус расходы)')
            ],
            [
                'value' => "envd",
                'label' => Mage::helper('kkm')->__('единый налог на вмененный доход')
            ],
            [
                'value' => "esn",
                'label' => Mage::helper('kkm')->__('единый сельскохозяйственный налог')
            ],
            [
                'value' => "patent",
                'label' => Mage::helper('kkm')->__('патентная СН')
            ]
        ];
    }
}
