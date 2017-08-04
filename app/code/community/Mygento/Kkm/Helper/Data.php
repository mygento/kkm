<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Data extends Mage_Core_Helper_Abstract
{
    use Mygento_Kkm_Helper_Logger_Db;

    protected $_code = 'kkm';

    /**
     *
     * @param type $text
     */
    public function addLog($text, $severity = Zend_Log::DEBUG)
    {
        $logsToDb = method_exists($this, 'writeLog');

        if (!Mage::getStoreConfig('kkm/general/debug')) {
            return false;
        }

        if ($severity > Mage::getStoreConfig('kkm/general/debug_level')) {
            return false;
        }

        try {
            if ($logsToDb) {
                $this->writeLog($text, $severity);

                return true;
            }
        } catch (Exception $e) {
            Mage::log('Attempt to write log to DB failed. Reason: ' . $e->getMessage(), null, '', true);
        }

        Mage::log($text, null, $this->getLogFilename(), true);
    }

    public function processError(Mygento_Kkm_SendingException $e)
    {
        $this->addLog($e->getMessage(), $e->getSeverity());

        if ($this->getConfig('email_notif_enabled') && $e->getSeverity() <= $this->getConfig('send_notif_severity')) {
            $this->sendNotificationEmail();
        }

        if ($this->getConfig('show_notif_in_admin') && $e->getSeverity() <= $this->getConfig('send_notif_severity')) {
            $this->showNotification('KKM Error', $e->getMessage());
        }

    }

    public function getLogFilename()
    {
        return 'kkm.log';
    }

    /**
     *
     * @param type string
     * @return mixed
     */
    public function getConfig($param)
    {
        $param = strpos($param, '/') !== false ? $param : 'general/' . $param;

        return Mage::getStoreConfig('kkm/' . $param);
    }

    public function getVendorModel()
    {
        return Mage::getModel('kkm/vendor_' . $this->getConfig('general/vendor'));
    }

    public function requestApiPost($url, $arpost)
    {
        // @codingStandardsIgnoreStart
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($arpost) ? http_build_query($arpost) : $arpost);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->addLog('Curl error: ' . curl_error($ch), Zend_Log::CRIT);
            return false;
        }
        curl_close($ch);
        // @codingStandardsIgnoreEnd
        $this->addLog($result);
        return $result;
    }

    public function requestApiGet($url)
    {
        // @codingStandardsIgnoreStart
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->addLog('Curl error: ' . curl_error($ch), Zend_Log::CRIT);
            return false;
        }
        curl_close($ch);
        // @codingStandardsIgnoreEnd
        $this->addLog($result);
        return $result;
    }

    //single function for sum
    public function calcSum($receipt)
    {
        if ($receipt->getOrderCurrencyCode() != 'RUB') {
            return round(Mage::helper('directory')->currencyConvert(
                $receipt->getBaseGrandTotal(),
                $receipt->getBaseCurrencyCode(),
                'RUB'
            ), 2);
        }
        return round($receipt->getGrandTotal(), 2);
    }

    /**
     * @param $json
     * @param $entityId string external_id from kkm/status table. Ex: 'invoice_100000023', 'creditmemo_1000002'
     */
    public function updateKkmInfoInOrder($json, $externalId, $vendor = 'atol')
    {
        $incrementId   = substr($externalId, strpos($externalId, '_') + 1);
        $entityType    = substr($externalId, 0, strpos($externalId, '_'));

        $entity = null;
        if (strpos($entityType, 'invoice') !== false) {
            $entity = Mage::getModel('sales/order_invoice')->load($incrementId, 'increment_id');
        } elseif (strpos($entityType, 'creditme') !== false) {
            $entity = Mage::getModel('sales/order_creditmemo')->load($incrementId, 'increment_id');
        }

        if (!$entity || empty($incrementId) || !$entity->getId()) {
            $this->addLog("Error. Can not save callback info to order. Method params: Json = {$json} 
        Extrnal_id = {$externalId}. Incrememnt_id = {$incrementId}. Entity_type = {$entityType}", Zend_Log::ERR);

            return false;
        }

        $this->saveTransactionInfoToOrder($json, $entity, $entity->getOrder(), 'Received message from KKM vendor.', $vendor);
    }

    public function hasOrderFailedKkmTransactions($order)
    {
        $invoices    = Mage::getModel('sales/order_invoice')->getCollection()
            ->addAttributeToFilter('order_id', ['eq' => $order->getId()]);
        $creditmemos = Mage::getModel('sales/order_creditmemo')->getCollection()
            ->addAttributeToFilter('order_id', ['eq' => $order->getId()]);

        $invoicesIncIds    = $invoices->getColumnValues('increment_id');
        $creditmemosIncIds = $creditmemos->getColumnValues('increment_id');

        $invoicesIncIds = array_map(
            function ($v) {
                return 'invoice_' . $v;
            },
            $invoicesIncIds
        );

        $creditmemosIncIds = array_map(
            function ($v) {
                return 'creditmemo_' . $v;
            },
            $creditmemosIncIds
        );

        $statuses = Mage::getModel('kkm/status')->getCollection()
            ->addFieldToFilter(
                'external_id',
                ['in' => array_merge($invoicesIncIds, $creditmemosIncIds)]
            );

        foreach ($statuses as $status) {
            $statusObj = json_decode($status->getStatus());

            if ($statusObj->status == 'fail') {
                $this->addLog("Order {$order->getId()} has failed kkm transaction. Id in table kkm/status: {$status->getId()}");

                return $status->getId();
            }
        }

        return false;
    }

    /**Save info about transaction to order
     * @param $getRequest string with json from vendor
     * @param $entity Invoice|Creditmemo
     * @param $order Order
     * @return bool
     */
    public function saveTransactionInfoToOrder($getRequest, $entity, $order, $orderComment = '', $vendorName = 'atol')
    {
        $status = false;

        try {
            $getRequestObj = json_decode($getRequest);

            if ($getRequestObj->error == null) {
                $orderComment = $orderComment ?: 'Cheque has been sent to KKM vendor.';
                $comment = '[' . strtoupper($vendorName) . '] '
                        . $this->__($orderComment) . ' '
                        . ucwords($entity::HISTORY_ENTITY_NAME) . ': '
                        . $entity->getIncrementId()
                        . '. Status: '
                        . ucwords($getRequestObj->status)
                        . '. Uuid: '
                        . $getRequestObj->uuid ?: 'no uuid';
            } else {
                $orderComment = $orderComment ?: 'Cheque has been rejected by KKM vendor.';
                $comment = '[' . strtoupper($vendorName) . '] '
                        . $this->__($orderComment) . ' '
                        . ucwords($entity::HISTORY_ENTITY_NAME) . ': '
                        . $entity->getIncrementId()
                        . '. Status: '
                        . ucwords($getRequestObj->status)
                        . '. Error code: '
                        . $getRequestObj->error->code
                        . '. Error text: '
                        . $getRequestObj->error->text
                        . '. Uuid: '
                        . $getRequestObj->uuid ?: 'no uuid';

                if ($this->getConfig('general/fail_status')) {
                    $status = $this->getConfig('general/fail_status');
                }
            }

            if ($status) {
                $order->setState('processing', $status, $comment);
            } else {
                $order->addStatusHistoryComment($comment);
            }

            $order->save();
        } catch (Exception $e) {
            $this->addLog('Can not save KKM transaction info to order. Reason: ' . $e->getMessage(), Zend_Log::CRIT);

            return false;
        }
    }

    /** Show Warning Notification in Dashboard
     *
     */
    public function showNotification($title, $description = '')
    {
        $notification = Mage::getModel('adminnotification/inbox');
        $notification->setDateAdded(date('Y-m-d H:i:s', time()));
        $notification->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_MAJOR);
        $notification->setTitle($title);
        $notification->setDescription($description);
        $notification->save();
    }

    /**
     * Send email KKM notification
     */
    public function sendNotificationEmail()
    {
//        $this->addLog('Sending  email to customers ' . (!is_null($customer) ?  $customer->getId() : 'no ID' ));

        $emails = explode(',', $this->getConfig('err_notif_emails'));
        $params = [];
        foreach ($emails as $email) {
            Mage::getModel('core/email_template')
                ->sendTransactional(
                    $this->getConfig('err_notif_template'),
                    $this->getConfig('err_notif_sender'), //It's sender
                    $email,
                    null,
                    $params
                );
        }
    }
}
