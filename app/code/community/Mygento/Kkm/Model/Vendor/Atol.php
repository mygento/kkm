<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Vendor_Atol extends Mygento_Kkm_Model_Abstract
{

    const _URL                 = 'https://online.atol.ru/possystem/v3/';
    const _code                = 'atol';
    const _operationSell       = 'sell';
    const _operationSellRefund = 'sell_refund';
    const _operationGetToken   = 'getToken';
    const _operationGetReport  = 'report';

    protected $token;

    /**
     *
     * @param type $invoice
     */
    public function sendCheque($invoice)
    {
        Mage::helper('kkm')->addLog('Start invoice ' . $invoice->getIncrementId() . ' processing');

        $this->sendToAtol($invoice);
    }

    /**
     *
     * @param type $invoice
     * @throws Exception
     */
    public function forceSendCheque($invoice)
    {
        Mage::helper('kkm')->addLog('Start FORCE invoice ' . $invoice->getIncrementId() . ' processing');
        $statusModel = Mage::getModel('kkm/status')->loadByEntity($invoice);
        if (!$statusModel->getId()) {
            throw new Exception(Mage::helper('kkm')->__("There is no transactions for %s. Force send works for existing transactions only.", 'invoice ' . $invoice->getIncrementId()));
        }

        $this->increaseExternalId($statusModel);
        $this->sendToAtol($invoice);
    }

    /**
     *
     * @param type $creditmemo
     */
    public function cancelCheque($creditmemo)
    {
        Mage::helper('kkm')->addLog('Start creditmemo ' . $creditmemo->getIncrementId() . ' processing');

        $this->sendToAtol($creditmemo);
    }

    /**
     *
     * @param type $creditmemo
     * @throws Exception
     */
    public function forceCancelCheque($creditmemo)
    {
        Mage::helper('kkm')->addLog('Start FORCE creditmemo ' . $creditmemo->getIncrementId() . ' processing');
        $statusModel = Mage::getModel('kkm/status')->loadByEntity($creditmemo);
        if (!$statusModel->getId()) {
            throw new Exception(Mage::helper('kkm')->__("There is no transactions for creditmemo %s. Force cancel works for existing transactions only.", $creditmemo->getIncrementId()));
        }

        $this->increaseExternalId($statusModel);
        $this->sendToAtol($creditmemo);
    }

    /**
     * @param $entity Invoice|Creditmemo
     * @throws Mygento_Kkm_SendingException
     */
    protected function sendToAtol($entity)
    {
        if (!$entity->getId()) {
            throw new Exception('Entity does not have an id. Class: ' . get_class($entity) . '.');
        }

        $helper      = Mage::helper('kkm');
        $type        = $entity::HISTORY_ENTITY_NAME;
        $debugData   = [];
        $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);
        $operation   = $type === 'invoice' ? self::_operationSell : self::_operationSellRefund;

        try {
            //Cheque is being sent for the 1st time
            if (!$statusModel->getId()) {
                $this->saveTransaction('{"initial":1}', $entity);
            }

            $jsonPost = $this->generateJsonPost($entity, $statusModel->getResendCount());
            $helper->addLog('Request to ATOL json: ' . $jsonPost);

            $token = $debugData['token'] = $this->getToken();

            $url = self::_URL . $this->getConfig('general/group_code') . '/' . $operation . '?tokenid=' . $token;
            $helper->addLog('url: ' . $url);

            $getRequest = $debugData['atol_response'] = $helper->requestApiPost($url, $jsonPost);

            $this->saveTransaction($getRequest, $entity);

            $this->validateResponse($getRequest);

            $helper->saveTransactionInfoToOrder($entity, $this->getCommentForOrder($getRequest), self::_code);

            //Note: Don't use finally {} here. Because there is no finally in PHP 5.4
        } catch (Mygento_Kkm_AtolException $e) {
            $getRequest = json_encode(['status' => 'fail', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            $this->saveTransaction($getRequest, $entity);

            throw new Mygento_Kkm_SendingException($entity, $e->getMessage(), $debugData);
        } catch (Exception $e) {
            $this->saveTransaction($getRequest, $entity);

            throw new Mygento_Kkm_SendingException($entity, $e->getMessage(), $debugData);
        }
    }

    /**Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     */
    public function saveTransaction($getRequest, $entity)
    {
        $type        = $entity::HISTORY_ENTITY_NAME;
        $request     = json_decode($getRequest);
        $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);
        $operation   = $type === 'invoice' ? self::_operationSell : self::_operationSellRefund;

        Mage::helper('kkm')->addLog(ucwords($entity::HISTORY_ENTITY_NAME) . 'Cheque getRequest ' . $getRequest);

        if (!$statusModel->getId()) {
            $statusModel->setVendor(self::_code)
                ->setExternalId($this->generateExternalId($entity))
                ->setOperation($operation);
        }

        $statusModel->setUuid(isset($request->uuid) ? $request->uuid : null)
            ->setShortStatus(isset($request->status) ? $request->status : null)
            ->setExternalId($this->generateExternalId($entity, $statusModel->getResendCount()))
            ->setEntityType($entity::HISTORY_ENTITY_NAME)
            ->setIncrementId($entity->getIncrementId())
            ->setStatus($getRequest)
            ->save();
    }

    /**Check and process existing transaction. Do not run it from observer.
     * @param $statusModel
     */
    public function processExistingTransactionBeforeSending($statusModel)
    {
        /**
         * Если WAIT - запросить состояние. Результат сохранить, проанализировать как FAIL (запустить этот метод заново)
         * Если FAIL - проанализировать код. В зависимости от кода ошибки - инкрементим ExternalID - или
         * оставить как есть
         */

// It does not work if method is invoked in loop
//        static $analyzeCount = 0;
//        $analyzeCount++;
//        if ($analyzeCount > 2) {
//            throw new Exception(Mage::helper('kkm')->__('Too many attempts to update status of the transaction.')
//                                . ' ' . ucfirst($statusModel->getEntityType())
//                                . ' ' . $statusModel->getIncrementId()
//            );
//        }

        $shortStatus = $statusModel->getShortStatus();

        //return true - if we need to send cheque to atol
        switch ($shortStatus) {
            case null:
                return true;

            case 'done':
                throw new Exception('Transaction is already done.');

            case 'wait':
                //check status. if changed - run analyze...() again!
                $this->updateStatus($statusModel->getUuid());
                $statusModel = $statusModel->load($statusModel->getId());

                if ($statusModel->getShortStatus() == $shortStatus) {
                    throw new Exception('Transaction is still processing');
                }

                return $this->processExistingTransactionBeforeSending($statusModel);

            case 'fail':
                break;

            default:
                throw new Exception('Unknown status');
        }

        $errorCode = $this->getErrorCode($statusModel->getStatus());

        //Ошибки при работе с ККТ (cash machine errors)
        if ((int)$errorCode < 0) {
            //increment EID and send it
            $this->increaseExternalId($statusModel);

            return true;
        }

        //Warning: Official documentation says we should not increase e_id in some cases. But Atol's engineers refute it:
        switch ($errorCode) {
            case '1': //Timeout
            case '2': //Incorrect INN (if type = agent) or incorrect Group_Code or Operation
            case '3': //Incorrect Operation
            case '8': //Validation error.
            case '22': //Incorrect group_code
                $this->increaseExternalId($statusModel);

                return true;
            case '10': //Документ с <external_id> и <group_code> уже существует в базе. Document exists in atol
                $this->updateStatus($statusModel->getUuid());
                $statusModel     = $statusModel->load($statusModel->getId());
                $atolResponseNew = json_decode($statusModel->getStatus(), true);
                $errorCodeNew    = isset($atolResponseNew['error']) && is_array($atolResponseNew['error']) ? $atolResponseNew['error']['code'] : null;
                if ($errorCodeNew == $errorCode) {
                    $this->increaseExternalId($statusModel);

                    return true;
                }

                return $this->processExistingTransactionBeforeSending($statusModel);
            case '11': //Incorrect request for checking status. Do not send it again. Just to fix request and check status
                $this->updateStatus($statusModel->getUuid());
                $statusModel     = $statusModel->load($statusModel->getId());
                $atolResponseNew = json_decode($statusModel->getStatus(), true);
                $errorCodeNew    = isset($atolResponseNew['error']) && is_array($atolResponseNew['error']) ? $atolResponseNew['error']['code'] : null;
                if ($errorCodeNew == $errorCode) {
                    throw new Exception('There are errors in checkStatus request.');
                }

                return $this->processExistingTransactionBeforeSending($statusModel);
            default:
                return false;
        }
    }

    protected function updateStatus($uuid)
    {
        $helper      = Mage::helper('kkm');
        $statusModel = Mage::getModel('kkm/status')->load($uuid, 'uuid');
        if (!$statusModel->getId()) {
            $helper->addLog('Uuid not found in store DB. Uuid: ' . $uuid, Zend_Log::ERR);

            return false;
        }

        $token = $this->getToken();

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationGetReport . '/' . $uuid . '?tokenid=' . $token;
        $helper->addLog('updateStatus of cheque: ' . $statusModel->getEntityType() . ' ' . $statusModel->getIncrementId());
        $helper->addLog('checkStatus url: ' . $url);

        $getRequest = $helper->requestApiGet($url);
        $request    = json_decode($getRequest);

        if ($statusModel->getStatus() == $getRequest) {
            return $statusModel;
        }
        $statusModel
            ->setShortStatus(isset($request->status) ? $request->status : null)
            ->setStatus($getRequest)
            ->save();

        //Add comment to order about callback data
        $helper->saveCallback($statusModel);

        return $statusModel;
    }

    /**
     * @param $uuid
     * @return bool
     * @throws Mygento_Kkm_SendingException
     */
    public function checkStatus($uuid)
    {
        $statusModel = $this->updateStatus($uuid);

        if (!$statusModel) {
            throw new Exception('Can not update transaction');
        }

        try {
            $this->validateResponse($statusModel->getStatus());
        } catch (Exception $e) {
            $incrementId = $statusModel->getIncrementId();
            $entityType  = $statusModel->getEntityType();

            if (strpos($entityType, 'invoice') !== false) {
                $entity = Mage::getModel('sales/order_invoice')->load($incrementId, 'increment_id');
            } elseif (strpos($entityType, 'creditme') !== false) {
                $entity = Mage::getModel('sales/order_creditmemo')->load($incrementId, 'increment_id');
            }

            throw new Mygento_Kkm_SendingException($entity, $e->getMessage(), ['uuid' => $statusModel->getUuid()]);
        }

        return true;
    }

    /**Returns External_Id for Atol
     *
     */
    public function generateExternalId($entity, $postfix = '')
    {
        return $entity::HISTORY_ENTITY_NAME . '_' . $entity->getIncrementId() . ($postfix ? "_{$postfix}" : '');
    }

    /**Returns Error code if transaction is failed
     *
     * @param string $status
     */
    public function getErrorCode($status)
    {
        $atolResponse = json_decode($status, true);

        if (!isset($atolResponse['error'])) {
            return null;
        }

        if (is_array($atolResponse['error']) && isset($atolResponse['error']['code'])) {
            return $atolResponse['error']['code'];
        }

        //Because of early bug $atolResponse['error'] might be a string
        preg_match('/Error code:\s\d+/', (string)$atolResponse['error'], $errorInfo);
        $errorText = array_shift($errorInfo);
        $errorCode = filter_var($errorText, FILTER_SANITIZE_NUMBER_INT);

        return $errorCode;
    }

    /**
     * 
     * @return boolean || string
     * @throws Exception
     */
    public function getToken($renew = false)
    {
        if (!$renew && $this->token) {
            return $this->token;
        }

        $data = [
            'login' => $this->getConfig('general/login'),
            'pass'  => Mage::helper('core')->decrypt($this->getConfig('general/password'))
        ];

        $getRequest = Mage::helper('kkm')->requestApiPost(self::_URL . self::_operationGetToken, json_encode($data));

        if (!$getRequest) {
            throw new Mygento_Kkm_AtolException(Mage::helper('kkm')->__('There is no response from Atol.'));
        }

        $decodedResult = json_decode($getRequest);

        if (!$decodedResult->token || $decodedResult->token == '') {
            throw new Mygento_Kkm_AtolException(Mage::helper('kkm')->__('Response from Atol does not contain valid token value. Response: ') . strval($getRequest));
        }

        $this->token = $decodedResult->token;

        return $this->token;
    }

    public function validateResponse($atolResponse)
    {
        $response = json_decode($atolResponse);

        if ($this->isResponseInvalid($response)) {
            throw new Mygento_Kkm_AtolException(Mage::helper('kkm')->__('Response from KKM vendor is empty or incorrect.'));
        }

        if ($this->isResponseFailed($response)) {
            throw new Exception($this->getCommentForOrder($atolResponse));
        }
    }

    /**
     * @param $receipt entity (Order, Invoice or Creditmemo)
     * @param $externalIdPostfix
     * @return string
     */
    public function generateJsonPost($receipt, $externalIdPostfix)
    {
        $discountHelper = Mage::helper('kkm/discount');

        $order = defined(get_class($receipt) . '::HISTORY_ENTITY_NAME') && $receipt::HISTORY_ENTITY_NAME == 'order'
            ? $receipt
            : $receipt->getOrder();

        $shipping_tax   = $this->getConfig('general/shipping_tax');
        $tax_value      = $this->getConfig('general/tax_options');
        $attribute_code = '';
        if (!$this->getConfig('general/tax_all')) {
            $attribute_code = $this->getConfig('general/product_tax_attr');
        }

        if (!$this->getConfig('general/default_shipping_name')) {
            $order->setShippingDescription($this->getConfig('general/custom_shipping_name'));
        }

        //Set mode flags for Discount logic
        $discountHelper->setDoCalculation(boolval($this->getConfig('general/apply_algorithm')));
        if ($this->getConfig('general/apply_algorithm')) {
            $discountHelper->setSpreadDiscOnAllUnits(boolval($this->getConfig('general/spread_discount')));
            $discountHelper->setIsSplitItemsAllowed(boolval($this->getConfig('general/split_allowed')));
        }

        $recalculatedReceiptData          = $discountHelper->getRecalculated($receipt, $tax_value, $attribute_code, $shipping_tax);
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);

        $callbackUrl = $this->getConfig('general/callback_url') ?: Mage::getUrl('kkm/index/callback', ['_secure' => true]);

        $now_time = Mage::getModel('core/date')->timestamp(time());
        $post = [
            'external_id' => $this->generateExternalId($receipt, $externalIdPostfix),
            'service' => [
                'payment_address' => $this->getConfig('general/payment_address'),
                'callback_url'    => $callbackUrl,
                'inn'             => $this->getConfig('general/inn')
            ],
            'timestamp' => date('d-m-Y H:i:s', $now_time),
            'receipt' => [],
        ];

        $receiptTotal = round($receipt->getGrandTotal(), 2);

        $post['receipt'] = [
            'attributes' => [
                'sno'   => $this->getConfig('general/sno'),
                'phone' => $this->getConfig('general/send_phone') ? $order->getShippingAddress()->getTelephone() : '',
                'email' => $order->getCustomerEmail(),
            ],
            'total'    => $receiptTotal,
            'payments' => [],
            'items' => [],
        ];

        $post['receipt']['payments'][] = [
            'sum'  => $receiptTotal,
            'type' => 1
        ];

        $recalculatedReceiptData['items'] = array_map([$this, 'sanitizeItem'], $recalculatedReceiptData['items']);
        $post['receipt']['items'] = $recalculatedReceiptData['items'];

        return json_encode($post);
    }

    public function sanitizeItem($item)
    {
        $strHelper    = Mage::helper('core/string');
        $item['name'] = $item['name'] && $strHelper->strlen($item['name']) > 64 ? $strHelper->truncate($item['name'], 64) : $item['name'];
        $taxes        = Mage::helper('kkm')->array_column(Mage::getModel('kkm/source_taxoption')->toOptionArray(), 'value');

        //isset() returns false if 'tax' exists but equal to NULL.
        if (array_key_exists('tax', $item) && !in_array($item['tax'], $taxes)) {
            $message = Mage::helper('kkm')->__("Product %s has invalid tax", $item['name']);
            throw new Exception($message);
        }

        return $item;
    }

    public function isResponseInvalid($response)
    {
        return (!$response || !property_exists($response, 'error'));
    }

    public function isResponseFailed($response)
    {
        return (property_exists($response, 'error') && $response->error !== null && $response->status == 'fail');
    }

    protected function increaseExternalId($statusModel)
    {
        $statusModel->setResendCount($statusModel->getResendCount() + 1);
        $statusModel->save();
    }

    /**
     * @param $atolResponse string json
     */
    public function getCommentForOrder($atolResponse, $message = '')
    {
        $responseObj  = json_decode($atolResponse);
        $orderComment = $this->isResponseFailed($responseObj) ? '' /* 'Cheque has been rejected by KKM vendor.'*/ : 'Cheque has been sent to KKM vendor.';
        $orderComment = $message ?: $orderComment;

        $com = Mage::helper('kkm')->__($orderComment) .
        ' Status: '
        . ucwords($responseObj->status)
        . '. Uuid: '
        . $responseObj->uuid ?: 'no uuid';

        $com .= $responseObj && $this->isResponseFailed($responseObj)
            ? '. Error code: '
            . $responseObj->error->code
            . '. Error text: '
            . $responseObj->error->text
            . '. Type: '
            . $responseObj->error->type
            : '';

        return $com;
    }
}
