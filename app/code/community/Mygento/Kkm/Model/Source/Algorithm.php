<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Algorithm
{

    const NO_ALGORITHM_VALUE              = 'none';
    const KOP_TO_SHIPPING_ALGORITHM_VALUE = 1;

    /**
     * Options source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NO_ALGORITHM_VALUE,
                'label' => Mage::helper('kkm')->__('Не применять')
            ],
            [
                'value' => self::KOP_TO_SHIPPING_ALGORITHM_VALUE,
                'label' => Mage::helper('kkm')->__('Остаток добавлять в сумму доставки')
            ],
        ];
    }
}
