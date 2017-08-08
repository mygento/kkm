<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
abstract class Mygento_Kkm_Model_Abstract
{
    const ORDER_KKM_FAILED_STATUS = 'kkm_failed';

    abstract public function sendCheque($invoice, $order);
    abstract public function cancelCheque($creditmemo, $order);
    abstract public function checkStatus($uuid);
    abstract public function processExistingTransactionBeforeSending($uuid);
    abstract public function isResponseInvalid(stdClass $response);
    abstract public function isResponseFailed(stdClass $response);

    /**
     *
     * @param type $param
     * @return type
     */
    protected function getConfig($param)
    {
        return Mage::helper('kkm')->getConfig($param);
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
