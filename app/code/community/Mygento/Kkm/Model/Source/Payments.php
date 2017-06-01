<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Payments
{

    /**
     * Options source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return Mage::helper('payment')->getPaymentMethodList(true, true, true);
    }
}
