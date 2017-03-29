<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
abstract class Mygento_Kkm_Model_Abstract
{

    abstract protected function sendCheque();

    abstract protected function cancelCheque();

    abstract protected function updateCheque();

    /**
     * 
     * @param type $param
     * @return type
     */
    protected function getConfig($param)
    {
        return Mage::getStoreConfig('mygento/kkm/' . $param);
    }

    /**
     * 
     * @return type
     */
    protected function getVendor()
    {
        return $this->getConfig('vendor');
    }
}
