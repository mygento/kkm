<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Data extends Mage_Core_Helper_Abstract {

    public function addLog($text) {
        if (Mage::getStoreConfig('kkm/general/debug')) {
            Mage::log($text, null, 'kkm.log', true);
        }
    }

}
