<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

use Mage_Sales_Model_Order_Invoice as Invoice;
use Mage_Sales_Model_Order_Creditmemo as Creditmemo;

interface Mygento_Kkm_Model_Vendor_Interface
{
    /**
     *
     * @param Invoice $invoice
     */
    public function sendCheque($invoice);

    /**
     *
     * @param Invoice $invoice
     * @throws Exception
     */
    public function forceSendCheque($invoice);

    /**
     *
     * @param Creditmemo $creditmemo
     */
    public function cancelCheque($creditmemo);

    /**
     *
     * @param Creditmemo $creditmemo
     * @throws Exception
     */
    public function forceCancelCheque($creditmemo);

    /** Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     */
    public function saveTransaction($getRequest, $entity);

    /** Check and process existing transaction. Do not run it from observer.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param $statusModel
     */
    public function processExistingTransactionBeforeSending($statusModel);

    /**
     * @param $uuid
     * @return bool
     * @throws Mygento_Kkm_SendingException
     */
    public function checkStatus($uuid);

    /** Returns External_Id for Atol
     *
     */
    public function generateExternalId($entity, $postfix = '');

    /** Returns Error code if transaction is failed
     *
     * @param string $status
     */
    public function getErrorCode($status);

    /**
     * @param bool $renew
     * @return string
     * @throws \Mygento_Kkm_AtolException
     */
    public function getToken($renew = false);

    public function validateResponse($atolResponse);

    /**
     * @param $receipt entity (Order, Invoice or Creditmemo)
     * @param $externalIdPostfix
     * @return string
     */
    public function generateJsonPost($receipt, $externalIdPostfix);

    public function sanitizeItem($item);

    /**
     * @param object $response
     * @return boolean
     */
    public function isResponseInvalid($response);

    /**
     * @param object $response
     * @return boolean
     */
    public function isResponseFailed($response);

    /**
     * @param $atolResponse string json
     */
    public function getCommentForOrder($atolResponse, $message = '');
}