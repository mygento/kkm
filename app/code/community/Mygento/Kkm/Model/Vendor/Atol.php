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
     * @param type $order
     */
    public function sendCheque($invoice, $order)
    {
        Mage::helper('kkm')->addLog('Start invoice ' . $invoice->getIncrementId() . ' processing');

        $this->sendToAtol($invoice);
    }

    /**
     *
     * @param type $creditmemo
     * @param type $order
     */
    public function cancelCheque($creditmemo, $order)
    {
        Mage::helper('kkm')->addLog('Start creditmemo ' . $creditmemo->getIncrementId() . ' processing');

        $this->sendToAtol($creditmemo);
    }

    protected function sendToAtol($entity)
    {
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

            $jsonPost = $this->_generateJsonPost($entity, $entity->getOrder(), $statusModel->getResendCount());
            $helper->addLog('Request to ATOL json: ' . $jsonPost);

            $token = $debugData['token'] = $this->getToken();

            $url = self::_URL . $this->getConfig('general/group_code') . '/' . $operation . '?tokenid=' . $token;
            $helper->addLog('cancelCheque url: ' . $url);

            $getRequest = $debugData['atol_response'] = $helper->requestApiPost($url, $jsonPost);

            $this->saveTransaction($getRequest, $entity);

            $this->validateResponse($getRequest);

            $helper->saveTransactionInfoToOrder($getRequest, $entity, $entity->getOrder());
        } catch (Exception $e) {
            throw new Mygento_Kkm_SendingException($entity, $e->getMessage(), $debugData);
        }
    }

    /**Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     * @param $order
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

    /**TODO: Вызывать метод при нажатии на кнопку или при запуске кроном. Но не при запуске обсервером.
     * @param $statusModel
     */
    public function processExistingTransactionBeforeSending($statusModel)
    {
        /**
         * Если WAIT - запросить состояние. Результат сохранить, проанализировать как FAIL
         * Если FAIL - проанализировать код. Вдруг надо заинкрементить EID - или
         * оставить как есть - в зависимости от кода ошибки
         */

        static $analyzeCount = 0;
        $analyzeCount++;
        if ($analyzeCount > 2) {
            throw new Exception(Mage::helper('kkm')->__('Too many attempts to update status of the transaction.')
                                . ' ' . ucfirst($statusModel->getEntityType())
                                . ' ' . $statusModel->getIncrementId()
            );
        }

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
        }

        $atolResponse = json_decode($statusModel->getStatus(), true);
        $errorCode    = isset($atolResponse['error']) && is_array($atolResponse['error']) ? $atolResponse['error']['code'] : null;

        //Ошибки при работе с ККТ
        if ((int)$errorCode < 0) {
            $this->increaseExternalId($statusModel);

            //increment EID and send it
            return true;
        }

        switch ($errorCode) {
            case '1': //Timeout
                $this->increaseExternalId($statusModel);

                return true;
            case '10': //Документ с <external_id> и <group_code> уже существует в базе
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
        $helper->addLog('checkStatus url: ' . $url);

        $getRequest = $helper->requestApiGet($url);
        $request    = json_decode($getRequest);

        if ($statusModel->getStatus() == $getRequest) {
            return $getRequest;
        }
        $statusModel
            ->setShortStatus(isset($request->status) ? $request->status : null)
            ->setStatus($getRequest)
            ->save();

        return $getRequest;
    }

    /**
     * @param $uuid
     * @return bool
     * @throws Mygento_Kkm_SendingException
     */
    public function checkStatus($uuid)
    {
        $helper      = Mage::helper('kkm');
        $statusModel = Mage::getModel('kkm/status')->load($uuid, 'uuid');

        $getRequest = $this->updateStatus($uuid);

        try {
            $this->validateResponse($getRequest);

            //Add comment to order about callback data
            $helper->updateKkmInfoInOrder($getRequest, $statusModel);
        } catch (Exception $e) {
            $incrementId = $statusModel->getIncrementId();
            $entityType  = $statusModel->getEntityType();

            if (strpos($entityType, 'invoice') !== false) {
                $entity = Mage::getModel('sales/order_invoice')->load($incrementId, 'increment_id');
            } elseif (strpos($entityType, 'creditme') !== false) {
                $entity = Mage::getModel('sales/order_creditmemo')->load($incrementId, 'increment_id');
            }

            throw new Mygento_Kkm_SendingException($entity, $e->getMessage(), ['uuid' => $uuid]);
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
            throw new Exception(Mage::helper('kkm')->__('There is no response from Atol.'));
        }

        $decodedResult = json_decode($getRequest);

        if (!$decodedResult->token || $decodedResult->token == '') {
            throw new Exception(Mage::helper('kkm')->__('Response from Atol does not contain valid token value. Response: ') . strval($getRequest));
        }

        $this->token = $decodedResult->token;

        return $this->token;
    }

    public function validateResponse($atolResponse)
    {
        $response = json_decode($atolResponse);

        //TODO: Заменить на isResponseInvalid
        if (!$atolResponse || !$response || !property_exists($response, 'error')) {
            throw new Exception(Mage::helper('kkm')->__('Response from KKM vendor is empty or incorrect.'));
        }

        //TODO: Заменить на isResponseFailed?
        //и использоовать в обсервере

        if ($response->error !== null && $response->status != 'wait') {
            throw new Exception($response->error->text
                                . '. Error code: ' . $response->error->code
                                . '. Type: ' . $response->error->type
            );
        }
    }

    /**
     * @param $receipt entity (Invoice or Creditmemo)
     * @param $order
     * @param $externalIdPostfix
     * @return string
     */
    protected function _generateJsonPost($receipt, $order, $externalIdPostfix)
    {
        $discountHelper = Mage::helper('kkm/discount');

        $shipping_tax   = $this->getConfig('general/shipping_tax');
        $tax_value      = $this->getConfig('general/tax_options');
        $attribute_code = '';
        if (!$this->getConfig('general/tax_all')) {
            $attribute_code = $this->getConfig('general/product_tax_attr');
        }

        if (!$this->getConfig('general/default_shipping_name')) {
            $receipt->getOrder()->setShippingDescription($this->getConfig('general/custom_shipping_name'));
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
        $item['name'] = $item['name'] && mb_strlen($item['name']) > 64 ? mb_strimwidth($item['name'], 0, 64) : $item['name'];
        $taxes        = array_column(Mage::getModel('kkm/source_taxoption')->toOptionArray(), 'value');

        //isset() returns false if 'tax' exists but equal to NULL.
        if (array_key_exists('tax', $item) && !in_array($item['tax'], $taxes)) {
            $message = Mage::helper('kkm')->__("Product %s has invalid tax", $item['name']);
            throw new Exception($message);
        }

        return $item;
    }

    protected function increaseExternalId($statusModel)
    {
        $statusModel->setResendCount($statusModel->getResendCount() + 1);
        $statusModel->save();
    }

//    /**
//     * @param $json
//     * @param $entityId string external_id from kkm/status table. Ex: 'invoice_100000023', 'creditmemo_1000002'
//     */
//    public function updateKkmInfoInOrder($json, $statusModel)
//    {
//        if (!$statusModel->getId()) {
//            $this->addLog("Error. Can not save callback info to order. StatusModel not found. Message from KKM = {$json}", Zend_Log::WARN);
//
//            return false;
//        }
//
//        $incrementId = $statusModel->getIncrementId();
//        $entityType  = $statusModel->getEntityType();
//
//        $entity = null;
//        if (strpos($entityType, 'invoice') !== false) {
//            $entity = Mage::getModel('sales/order_invoice')->load($incrementId, 'increment_id');
//        } elseif (strpos($entityType, 'creditme') !== false) {
//            $entity = Mage::getModel('sales/order_creditmemo')->load($incrementId, 'increment_id');
//        }
//
//        if (!$entity || empty($incrementId) || !$entity->getId()) {
//            $this->addLog("Error. Can not save callback info to order. Method params: Json = {$json}
//        Extrnal_id = {$statusModel->getExternalId()}. Incrememnt_id = {$incrementId}. Entity_type = {$entityType}", Zend_Log::WARN);
//
//            return false;
//        }
//
//        $this->saveTransactionInfoToOrder($json, $entity, $entity->getOrder(), '', self::_code);
//    }

}
