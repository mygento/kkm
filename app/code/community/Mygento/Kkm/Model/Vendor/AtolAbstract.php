<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
abstract class Mygento_Kkm_Model_Vendor_AtolAbstract implements Mygento_Kkm_Model_Vendor_VendorInterface
{
    const CODE                 = 'atol';
    const OPERATION_SELL       = 'sell';
    const OPERATION_REFUND     = 'sell_refund';
    const OPERATION_GET_TOKEN  = 'getToken';
    const OPERATION_GET_REPORT = 'report';

    const PAYMENT_TYPE_BASIC = 1;

    protected $token;

    abstract protected function getUrl();
    abstract public function generateJsonPost($receipt, $externalIdPostfix);
    abstract protected function getSendUrl($operation);
    abstract protected function getUpdateStatusUrl($uuid);

    /**
     * @param \Mage_Sales_Model_Order_Invoice $invoice
     * @throws \Mygento_Kkm_SendingException
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
        $operation   = $type === 'invoice' ? self::OPERATION_SELL : self::OPERATION_REFUND;
        $getRequest  = '{"initial":1}';

        try {
            //Cheque is being sent for the 1st time
            if (!$statusModel->getId()) {
                $this->saveTransaction($getRequest, $entity);
            }

            $jsonPost = $this->generateJsonPost($entity, $statusModel->getResendCount());

            $helper->addLog('Request to ATOL json: ' . $jsonPost);

            $token = $debugData['token'] = $this->getToken();

            $url = $this->getSendUrl($operation);
            $headers[] = "Token: $token";
            $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
            $helper->addLog('url: ' . $url);

            $getRequest = $debugData['atol_response'] = $helper->requestApiPost($url, $jsonPost, $headers);

            $this->saveTransaction($getRequest, $entity);

            $this->validateResponse($getRequest);

            $helper->saveTransactionInfoToOrder($entity, $this->getCommentForOrder($getRequest), self::CODE);

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

    /** Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     */
    public function saveTransaction($getRequest, $entity)
    {
        $type        = $entity::HISTORY_ENTITY_NAME;
        $request     = json_decode($getRequest);
        $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);
        $operation   = $type === 'invoice' ? self::OPERATION_SELL : self::OPERATION_REFUND;

        Mage::helper('kkm')->addLog(ucwords($entity::HISTORY_ENTITY_NAME) . 'Cheque getRequest ' . $getRequest);

        if (!$statusModel->getId()) {
            $statusModel->setVendor(self::CODE)
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

    /**
     * @param $statusModel
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return bool|void
     * @throws \Exception
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
        $headers[] = "Token: $token";
        $url = $this->getUpdateStatusUrl($uuid);

        $helper->addLog('updateStatus of cheque: ' . $statusModel->getEntityType() . ' ' . $statusModel->getIncrementId());
        $helper->addLog('checkStatus url: ' . $url);

        $getRequest = $helper->requestApiGet($url, $headers);
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

    /** Returns External_Id for Atol
     *
     */
    public function generateExternalId($entity, $postfix = '')
    {
        return $entity::HISTORY_ENTITY_NAME . '_' . $entity->getIncrementId() . ($postfix ? "_{$postfix}" : '');
    }

    /** Returns Error code if transaction is failed
     *
     * @param string $status
     * @return int|null
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
     * @param bool $renew
     * @return string
     * @throws \Mygento_Kkm_AtolException
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

        $getRequest = Mage::helper('kkm')->requestApiPost($this->getUrl() . self::OPERATION_GET_TOKEN, json_encode($data));

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
            . ($responseObj->uuid ?: 'no uuid');

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

    /**
     *
     * @param string $param
     * @return mixed
     */
    protected function getConfig($param)
    {
        return Mage::helper('kkm')->getConfig($param);
    }
}
