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

    abstract public function sendCheque($invoice);
    abstract public function cancelCheque($creditmemo);
    abstract public function checkStatus($uuid);
    abstract public function isResponseInvalid(stdClass $response);
    abstract public function isResponseFailed(stdClass $response);

    public function forceSendCheque($invoice)
    {
        $this->sendCheque($invoice);
    }

    public function forceCancelCheque($creditmemo)
    {
        $this->cancelCheque($creditmemo);
    }

    /**
     *
     * @SuppressWarnings("unused")
     */
    public function processExistingTransactionBeforeSending($uuid)
    {
    }

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
