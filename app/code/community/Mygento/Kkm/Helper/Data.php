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
        $log = $e->getMessage() . ' ' . $e->getExtraMessage();
        $this->addLog($log, $e->getSeverity());

        if ($this->getConfig('show_notif_in_admin')) {
            $this->showNotification($this->__('The cheque has not been sent to KKM.'), $e->getMessage());
        }

        if ($this->getConfig('email_notif_enabled')) {
            $this->sendNotificationEmail($e->getFailTitle(), $e->getOrderId(), $e->getReason(), $e->getExtraData());
        }

        $this->saveTransactionInfoToOrder($e->getEntity(), $e->getMessage(), $this->getConfig('vendor'), true);
//        $this->setOrderFailStatus(Mage::getModel('sales/order')->load($e->getOrderId(), 'increment_id'), $e->getFullTitle());
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

    public function saveCallback($statusModel)
    {
        if (!$statusModel->getId()) {
            $this->addLog("Error. Can not save callback info to order. StatusModel not found.", Zend_Log::WARN);

            return false;
        }

        $entity = $this->getEntityModelByStatusModel($statusModel);

        if (!$entity->getId()) {
            $this->addLog("Error. Can not save callback info to order. "
                          . "Method params: Json = {$statusModel->getStatus()}. "
                          . "Extrnal_id = {$statusModel->getExternalId()}. Incrememnt_id = {$statusModel->getIncrementId()}. "
                          . "Entity_type = {$statusModel->getEntityType()}", Zend_Log::WARN);

            return false;
        }

        $comment = $this->getVendorModel()->getCommentForOrder($statusModel->getStatus(), $this->__('Received message from KKM vendor.'));

        $this->saveTransactionInfoToOrder($entity, $comment, $statusModel->getVendor());
    }

    public function getEntityModelByStatusModel($statusModel)
    {
        $incrementId = $statusModel->getIncrementId();
        $entityType  = $statusModel->getEntityType();
        $entity      = new Varien_Object();

        if (strpos($entityType, 'invoice') !== false) {
            $entity = Mage::getModel('sales/order_invoice')->load($incrementId, 'increment_id');
        } elseif (strpos($entityType, 'creditme') !== false) {
            $entity = Mage::getModel('sales/order_creditmemo')->load($incrementId, 'increment_id');
        }

        return $entity;
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
                return ['type' => 'invoice', 'incrementId' => $v];
            },
            $invoicesIncIds
        );

        $creditmemosIncIds = array_map(
            function ($v) {
                return ['type' => 'creditmemo', 'incrementId' => $v];
            },
            $creditmemosIncIds
        );

        foreach (array_merge($invoicesIncIds, $creditmemosIncIds) as $incId) {
            $entity      = Mage::getModel('sales/order_' . $incId['type'])->load($incId['incrementId'], 'increment_id');
            $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);

            if (!$statusModel->getId()) {
                $this->addLog("Order {$order->getId()}: cheque {$incId['type']} {$incId['incrementId']} was not sent to KKM.", Zend_Log::WARN);

                return true;
            }

            if ($this->getVendorModel()->isResponseFailed(json_decode($statusModel->getStatus()))) {
                $this->addLog("Order {$order->getId()} has failed kkm transaction. Id in table kkm/status: {$statusModel->getId()}. 
                {$statusModel->getEntityType()} {$statusModel->getIncrementId()}");

                return $statusModel->getId();
            }
        }

        return false;
    }

    /**Save info about transaction to order
     * @param $entity Invoice|Creditmemo
     * @param $comment string comment with necessary info (Status, Uuid, )
     * @param $vendorName string
     * @param $isError bool
     * @return bool
     */
    public function saveTransactionInfoToOrder(Varien_Object $entity, $comment, $vendorName, $isError = false)
    {
        $order = $entity->getOrder();

        try {
            $status          = $isError && $this->getConfig('fail_status') ? $this->getConfig('fail_status') : null;
            $preparedComment = '[' . strtoupper($vendorName) . '] '
                . $this->__($comment) . ' '
                . ucwords($entity::HISTORY_ENTITY_NAME) . ': '
                . $entity->getIncrementId();

            if ($status) {
                $order->setStatus($status);
//                $order->setState('processing', $status, $comment);
            }

//            $order->setKkmChangeStatusFlag(true);
            $order->addStatusHistoryComment($preparedComment);
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

    /**Workaround for php 5.3 and 5.4
     * @param $array
     * @param $columnName
     * @return array
     */
    public function array_column($array, $columnName) // @codingStandardsIgnoreLine
    {
        return array_map(function ($element) use ($columnName) {
            return $element[$columnName];
        }, $array);
    }

    /**
     * Send email KKM notification
     */
    public function sendNotificationEmail($title, $orderId = '', $reason = '', $extra = [])
    {
        $emails = explode(',', $this->getConfig('err_notif_emails'));

        $params = [
            'title'   => $title,
            'orderId' => $orderId,
            'reason'  => $reason,
            'extra'   => json_encode($extra)
        ];

        $this->addLog('Sending  email to users: ' . $this->getConfig('err_notif_emails'));
        $this->addLog('Params for letter: ' . json_encode($params));

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
