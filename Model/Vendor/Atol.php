<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Vendor;

/**
 * class Atol
 */
class Atol extends \Mygento\Kkm\Model\AbstractModel
{

    /**
     * Constants
     */
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
        $type   = 'invoice';
        $helper = $this->_kkmHelper;
        try {
            $token = $this->getToken();
        } catch (\Exception $e) {
            $helper->addLog($e->getMessage(), \Zend\Log\Logger::ERR);

            return false;
        }

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationSell . '?tokenid=' . $token;
        $helper->addLog('sendCheque url: ' . $url);

        $jsonPost = $this->_generateJsonPost($type, $invoice, $order);
        $helper->addLog('sendCheque jsonPost: ' . $jsonPost);

        $getRequest = $helper->requestApiPost($url, $jsonPost);

        $this->saveTransaction($getRequest, $invoice, $order);
    }

    /*     * Method saves status entity and writes info to order
     * @param $getRequest
     * @param $entity
     * @param $order
     */
    public function saveTransaction($getRequest, $entity, $order)
    {
        $type      = $entity->getEntityType();
        $operation = $entity->getEntityType() == 'invoice' ? self::_operationSell : self::_operationSellRefund;
        $helper    = $this->_kkmHelper;

        $helper->addLog(ucwords($entity->getEntityType()) . 'Cheque getRequest ' . $getRequest);

        if ($getRequest) {
            $request     = json_decode($getRequest);
            $statusModel = $this->_statusFactory->create()->getCollection()
                ->addFieldToFilter('type', $type)
                ->addFieldToFilter('increment_id', $entity->getIncrementId())
                ->getFirstItem();

            if (!$statusModel->getId()) {
                $statusModel->setVendor(self::_code);
                $statusModel->setType($type);
                $statusModel->setIncrementId($entity->getIncrementId());
                $statusModel->setOperation($operation);
                $statusModel->setStatus($request->status);
            }

            $statusModel->setUuid($request->uuid);
            $statusModel
                ->setResponse($getRequest)
                ->save();

            //Save info about transaction
            $helper->saveTransactionInfoToOrder($getRequest, $entity, $order);
        }
    }

    /**
     * 
     * @param type $creditmemo
     * @param type $order
     */
    public function cancelCheque($creditmemo, $order)
    {
        $type   = 'creditmemo';
        $helper = $this->_kkmHelper;

        try {
            $token = $this->getToken();
        } catch (\Exception $e) {
            $helper->addLog($e->getMessage(), \Zend\Log\Logger::ERR);
            return false;
        }

        $url = self::_URL . $this->getConfig('general/group_code') . '/' . self::_operationSellRefund . '?tokenid=' . $token;
        $helper->addLog('cancelCheque url: ' . $url);

        $jsonPost = $this->_generateJsonPost($type, $creditmemo, $order);
        $helper->addLog('cancelCheque jsonPost: ' . $jsonPost);

        $getRequest = $helper->requestApiPost($url, $jsonPost);

        $this->saveTransaction($getRequest, $creditmemo, $order);
    }

    /**
     * 
     * @param type $invoice
     */
    public function updateCheque($invoice)
    {
        
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
        $helper = $this->_kkmHelper;

        $data = [
            'login' => $this->getConfig('general/login'),
            'pass'  => $helper->decrypt($this->getConfig('general/password'))
        ];

//        $helper->addLog('sendCheque getToken $data: ' . print_r($data));

        $getRequest = $this->_kkmHelper->requestApiPost(self::_URL . self::_operationGetToken,
            json_encode($data));

        if (!$getRequest) {
            throw new \Exception(__('There is no response from Atol.'));
        }

        $decodedResult = json_decode($getRequest);

        if (!$decodedResult->token || $decodedResult->token == '') {
            throw new \Exception(__('Response from Atol does not contain valid token value. Response: ') . strval($getRequest));
        }

        $this->token = $decodedResult->token;

        return $this->token;
    }

    /**
     * 
     * @param type $type || string
     * @param type $receipt
     * @param type $order
     * @return type json
     */
    protected function _generateJsonPost($type, $receipt, $order)
    {
        $discountHelper = $this->_kkmDiscount;

        $shipping_tax   = $this->getConfig('general/shipping_tax');
        $tax_value      = $this->getConfig('general/tax_options');
        $attribute_code = '';
        if (!$this->getConfig('general/tax_all')) {
            $attribute_code = $this->getConfig('general/product_tax_attr');
        }

        if (!$this->getConfig('general/default_shipping_name')) {
            $receipt->getOrder()->setShippingDescription($this->getConfig('general/custom_shipping_name'));
        }

        $recalculatedReceiptData          = $discountHelper->getRecalculated($receipt, $tax_value,
            $attribute_code, $shipping_tax);
        $recalculatedReceiptData['items'] = array_values($recalculatedReceiptData['items']);

        $now_time = $this->_date->timestamp(time());
        $post     = [
            'external_id' => $type . '_' . $receipt->getIncrementId(),
            'service'     => [
                'payment_address' => $this->getConfig('general/payment_address'),
                'callback_url'    => $this->_storeManager->getStore()->getUrl('kkm/index/callback',
                    ['_secure' => true]),
                'inn'             => $this->getConfig('general/inn')
            ],
            'timestamp'   => date('d-m-Y H:i:s', $now_time),
            'receipt'     => [],
        ];

        $receiptTotal = round($receipt->getGrandTotal(), 2);

        $post['receipt'] = [
            'attributes' => [
                'sno'   => $this->getConfig('general/sno'),
                'phone' => $order->getShippingAddress()->getTelephone(),
                'email' => $order->getCustomerEmail(),
            ],
            'total'      => $receiptTotal,
            'payments'   => [],
            'items'      => [],
        ];

        $post['receipt']['payments'][] = [
            'sum'  => $receiptTotal,
            'type' => 1
        ];

        $recalculatedReceiptData['items'] = array_map([$this, 'sanitizeItem'],
            $recalculatedReceiptData['items']);
        $post['receipt']['items']         = $recalculatedReceiptData['items'];

        return json_encode($post);
    }

    public function sanitizeItem($item)
    {
        //isset() returns false if 'tax' exists but equal to NULL.
        if (array_key_exists('tax', $item)) {
            $item['tax'] = in_array($item['tax'],
                    ["none", "vat0", "vat10", "vat18", "vat110", "vat118"], true) ? $item['tax'] : "none";

            return $item;
        }
    }
    public function checkStatus($uuid)
    {
        die('work');
    }

}
