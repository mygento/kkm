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

            $jsonPost = $this->_generateJsonPost($entity, $entity->getOrder());
            $helper->addLog('Request to ATOL json: ' . $jsonPost);

            $token = $debugData['token'] = $this->getToken();

            $url = self::_URL . $this->getConfig('general/group_code') . '/' . $operation . '?tokenid=' . $token;
            $helper->addLog('cancelCheque url: ' . $url);

            //Cheque has been sent. Perhaps we have to increase external_id
            if ($statusModel->getId()) {
                $statusModel->setResendCount($statusModel->getResendCount() + 1);
                $jsonPost = json_decode($jsonPost, true);
                $jsonPost['external_id'] = $this->processExternalId($statusModel, $entity);
                $jsonPost = json_encode($jsonPost);
            }

            $getRequest = $debugData['atol_response'] = $helper->requestApiPost($url, $jsonPost);

            $this->saveTransaction($getRequest, $entity);

            $this->validateResponse($getRequest);

            //Save info about transaction
            Mage::helper('kkm')->saveTransactionInfoToOrder($getRequest, $entity, $entity->getOrder());
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
            ->setEntityType($entity::HISTORY_ENTITY_NAME)
            ->setIncrementId($entity->getIncrementId())
            ->setStatus($getRequest)
            ->save();
    }

    public function processExternalId($statusModel, $entity)
    {
        $atolResponse = json_decode($statusModel->getStatus(), true);
        $errorCode    = isset($atolResponse['error']) && is_array($atolResponse['error']) ? $atolResponse['error']['code'] : null;
        $postfix      = '';

        //TODO: ЗДЕСЬ НАДО ПОЧИНИТЬ
        $eid          = $statusModel->getExternalId();

        switch ($errorCode) {
            case '10':
                $postfix = '_' . $statusModel->getResendCount();
                break;

            default:
        }

        $eid .= $postfix;
        $statusModel->setExternalId($eid);
        $statusModel->save();

        return $eid;
    }

    public function checkStatus($uuid)
    {
        $helper      = Mage::helper('kkm');
        $statusModel = Mage::getModel('kkm/status')->load($uuid, 'uuid');

        if (!$statusModel->getId()) {
            $helper->addLog('Uuid not found in store DB. Uuid: ', Zend_Log::ERR);

            return false;
        }

        try {
            $token = $this->getToken();
        } catch (Exception $e) {
            $helper->addLog($e->getMessage(), Zend_Log::WARN);

            return false;
        }

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationGetReport . '/' . $uuid . '?tokenid=' . $token;
        $helper->addLog('checkStatus url: ' . $url);

        $getRequest = $helper->requestApiGet($url);
        $request    = json_decode($getRequest);

        if ($statusModel->getStatus() !== $getRequest) {
            $statusModel
                ->setShortStatus(isset($request->status) ? $request->status : null)
                ->setStatus($getRequest)
                ->save();

            //Add comment to order about callback data
            $helper->updateKkmInfoInOrder($getRequest, $statusModel->getExternalId());
        }

        return true;
    }

    /**Returns External_Id for Atol
     *
     */
    public function generateExternalId($entity)
    {
        return $entity::HISTORY_ENTITY_NAME . '_' . $entity->getIncrementId();
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

        if ($response->error !== null) {
            throw new Exception($response->error->text
                                . '. Error code: ' . $response->error->code
                                . '. Type: ' . $response->error->type
            );
        }
    }

    /**
     * 
     * @param type $type || string
     * @param type $receipt
     * @param type $order
     * @return type json
     */
    protected function _generateJsonPost($receipt, $order)
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

        $recalculatedReceiptData = $discountHelper->getRecalculated($receipt, $tax_value, $attribute_code, $shipping_tax);
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);

        $callbackUrl = $this->getConfig('general/callback_url') ?: Mage::getUrl('kkm/index/callback', ['_secure' => true]);

        $now_time = Mage::getModel('core/date')->timestamp(time());
        $post = [
            'external_id' => $this->generateExternalId($receipt),
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
}
