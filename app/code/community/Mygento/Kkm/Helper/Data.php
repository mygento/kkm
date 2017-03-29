<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * 
     * @param type $text
     */
    public function addLog($text)
    {
        if (Mage::getStoreConfig('kkm/general/debug')) {
            Mage::log($text, null, 'kkm.log', true);
        }
    }

    /**
     * 
     * @param type string
     * @return mixed
     */
    public function getConfig($param)
    {
        return Mage::getStoreConfig('mygento/kkm/' . $param);
    }
}
