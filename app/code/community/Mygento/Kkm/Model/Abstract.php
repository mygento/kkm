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

    /**
     * @param $response stdClass after json_decode()
     * @return bool
     */
    abstract public function isResponseInvalid($response);

    /**
     * @param $response stdClass after json_decode()
     * @return bool
     */
    abstract public function isResponseFailed($response);

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
     * @param string $param
     * @return mixed
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
