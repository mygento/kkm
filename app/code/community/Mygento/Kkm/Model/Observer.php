<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Observer
{

    /**
     *
     * @param type $observer
     */
    public function sendCheque($observer)
    {
        $helper = Mage::helper('kkm');
        $helper->addLog('sendCheque');

        $invoice = $observer->getEvent()->getInvoice();

        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_invoice') || $invoice->getOrderCurrencyCode() != 'RUB') {
            $helper->addLog('Skipped send cheque.');
            return;
        }

        $order           = $invoice->getOrder();
        $paymentMethod   = $order->getPayment()->getMethod();
        $invoiceOrigData = $invoice->getOrigData();
        $paymentMethods  = explode(',', $helper->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod . ' is not allowed for sending cheque.');
            return;
        }

        if ($invoice->getOrigData() && isset($invoiceOrigData['increment_id'])) {
            return;
        }

        try {
            $helper->getVendorModel()->sendCheque($invoice, $order);
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
        }
    }

    /**
     *
     * @param type $observer
     */
    public function cancelCheque($observer)
    {
        $helper = Mage::helper('kkm');
        $helper->addLog('cancelCheque');

        $creditmemo = $observer->getEvent()->getCreditmemo();
        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_cancel') || $creditmemo->getOrderCurrencyCode() !== 'RUB') {
            $helper->addLog('Skipped cancel cheque.');
            return;
        }

        $order              = $creditmemo->getOrder();
        $paymentMethod      = $order->getPayment()->getMethod();
        $creditmemoOrigData = $creditmemo->getOrigData();
        $paymentMethods     = explode(',', $helper->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod . ' is not allowed for cancelling cheque.');
            return;
        }
        if ($creditmemo->getOrigData() && isset($creditmemoOrigData['increment_id'])) {
            return;
        }

        try {
            $helper->getVendorModel()->cancelCheque($creditmemo, $order);
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
        }
    }

    /**Check and change order's status. if it has failed kkm transactions status should be kkm_failed.
     *If no - status should not be kkm_failed.
     * @param $observer
     */
    public function checkStatus($observer)
    {
        if (!Mage::helper('kkm')->getConfig('general/enabled')) {
            return;
        }

        $order      = $observer->getEvent()->getOrder();
        $vendorName = Mage::helper('kkm')->getConfig('general/vendor');

        if ($order->getKkmChangeStatusFlag()) {
            Mage::helper('kkm')->addLog("Attempt to change status of the order based on KKM transactions. Order: "
                                        . $order->getIncrementId() . ". New status: {$order->getStatus()}");

            return;
        }

        $isKkmFail       = Mage::helper('kkm')->hasOrderFailedKkmTransactions($order);
        $kkmFailedStatus = Mage::helper('kkm')->getConfig('general/fail_status');

        if ($isKkmFail && $order->getData('status') !== $kkmFailedStatus) {
            Mage::helper('kkm')->addLog("Order {$order->getId()} needs to change its status to {$kkmFailedStatus}");
            $order->setKkmChangeStatusFlag(true);
            $order->setStatus($kkmFailedStatus);

            $order->addStatusHistoryComment('[' . strtoupper($vendorName) . '] '
                                            . Mage::helper('kkm')->__('Status of the order has been changed automatically because it has failed KKM transactions.'));
            $order->save();
        }

        //Change order status if it no longer has failed invoices/creditmemos
        if (!$isKkmFail && $order->getData('status') === $kkmFailedStatus) {
            Mage::helper('kkm')->addLog("Order with id {$order->getId()} needs to change its status to default status of state '{$order->getState()}'");
            $order->setKkmChangeStatusFlag(true);
            $defaultOrderStatusModel = Mage::getModel('sales/order_status')
                ->loadDefaultByState($order->getData('state'));

            $defaultOrderStatus = $defaultOrderStatusModel ? $defaultOrderStatusModel->getStatus() : '';

            $order->setStatus($defaultOrderStatus ?: $order->getStatus());

            $order->addStatusHistoryComment('[' . strtoupper($vendorName) . '] '
                                            . Mage::helper('kkm')->__('Status of the order has been changed automatically because it no longer has failed KKM transactions.'));
            $order->save();
        }
    }

    public function updateTransactions()
    {
        $helper        = Mage::helper('kkm');
        $vendor        = $helper->getVendorModel();
        $autoSendLimit = $helper->getConfig('autosend_limit');

        if (!$helper->getConfig('enabled')) {
            return;
        }

        $waitStatuses        = Mage::getModel('kkm/status')->getCollection()
            ->addFieldToFilter('short_status', 'wait')
            //->addFieldToFilter('resend_count', [["null" => true], ['lt' => $autoSendLimit]])
        ;

        $filterNullTrue      = ['null' => true];
        $filterFailStatus    = ['in' => ['fail', null, '']];
        $filterLimitAutosend = ['lt' => $autoSendLimit];

        //Set 'OR' in SQL statement
        $failStatuses = Mage::getModel('kkm/status')->getCollection()
            ->addFieldToFilter('short_status', [$filterNullTrue, $filterFailStatus])
            ->addFieldToFilter('resend_count', [$filterNullTrue, $filterLimitAutosend]);

        $waitUpdated = 0;
        foreach ($waitStatuses as $waitStatus) {
            try {
                $result = $vendor->checkStatus($waitStatus->getUuid());

                $waitUpdated = $result ? $waitUpdated + 1 : $waitUpdated;
            } catch (Mygento_Kkm_SendingException $e) {
                $helper->processError($e);
                $waitUpdated++;
            } catch (Exception $e) {
                $helper->addLog($e->getMessage(), Zend_Log::WARN);
            }
        }

        $failUpdated = 0;
        foreach ($failStatuses as $failStatus) {
            $method = $failStatus->getEntityType() == 'creditmemo' ? 'cancelCheque' : 'sendCheque';
            $entity = $helper->getEntityModelByStatusModel($failStatus);

            try {
                $vendor->processExistingTransactionBeforeSending($failStatus);
                $vendor->$method($entity, $entity->getOrder());

                $failUpdated++;
            } catch (Mygento_Kkm_SendingException $e) {
                $helper->processError($e);

                $failUpdated++;
            } catch (Exception $e) {
                $helper->addLog($e->getMessage(), Zend_Log::WARN);
            }
        }

        $helper->addLog("{$waitUpdated} records with status 'wait' were successfully updated by CRON.", Zend_Log::WARN);
        $helper->addLog("{$failUpdated} records with status 'fail' were successfully resent or updated by CRON.", Zend_Log::WARN);
    }

    public function addExtraButtons($observer)
    {
        if (!Mage::helper('kkm')->getConfig('general/enabled')) {
            return;
        }

        $container = $observer->getBlock();
        if (!$this->isProperPageForResendButton($container)) {
            return;
        }

        $entity         = $container->getInvoice() ?: $container->getCreditmemo();
        $order          = $entity->getOrder();
        $paymentMethod  = $order->getPayment()->getMethod();
        $paymentMethods = explode(',', Mage::helper('kkm')->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods) || $entity->getOrderCurrencyCode() != 'RUB') {
            return;
        }

        $statusModel      = Mage::getModel('kkm/status')->loadByEntity($entity);
        $status           = json_decode($statusModel->getStatus());

        if ($this->canBeShownResendButton($statusModel)) {
            $url  = Mage::getModel('adminhtml/url')
                ->getUrl(
                    'adminhtml/kkm_cheque/resend',
                    [
                        'entity' => $entity::HISTORY_ENTITY_NAME,
                        'id'     => $entity->getId()
                    ]
                );
            $urlEnforce  = Mage::getModel('adminhtml/url')
                ->getUrl(
                    'adminhtml/kkm_cheque/forceresend',
                    [
                        'entity' => $entity::HISTORY_ENTITY_NAME,
                        'id'     => $entity->getId(),
                    ]
                );
            $data = [
                'label'   => Mage::helper('kkm')->__('Resend to KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];
            $dataEnforce = [
                'label'   => Mage::helper('kkm')->__('Force resend to KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $urlEnforce . '\')',
            ];

            $container->addButton('resend_to_kkm', $data);
            if (Mage::helper('kkm')->getConfig('force_resend')) {
                $container->addButton('force_resend_to_kkm', $dataEnforce);
            }
        } elseif ($this->canBeShownCheckStatusButton($statusModel)) {
            $url  = Mage::getModel('adminhtml/url')
                ->getUrl(
                    'adminhtml/kkm_cheque/checkstatus',
                    [
                        'uuid' => $status->uuid
                    ]
                );
            $data = [
                'label'   => Mage::helper('kkm')->__('Check status in KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $container->addButton('check_status_in_kkm', $data);
        }

        return $this;
    }

    /**Check is current page appropriate for "resend to kkm" button
     *
     * @param type $block
     * @return boolean
     */
    protected function isProperPageForResendButton($block)
    {
        return (null !== $block && ($block->getType() == 'adminhtml/sales_order_creditmemo_view' || $block->getType() == 'adminhtml/sales_order_invoice_view'));
    }

    protected function canBeShownResendButton($statusModel)
    {
        //Check ACL
        $resendAllowed = Mage::getSingleton('admin/session')->isAllowed('kkm_cheque/resend');
        $status        = json_decode($statusModel->getStatus());

        return ($resendAllowed && (!$statusModel->getId() || !$status || !property_exists($status, 'uuid') || (isset($status->status) && $status->status == 'fail')));
    }

    protected function canBeShownCheckStatusButton($statusModel)
    {
        //Check ACL
        $checkStatusAllowed = Mage::getSingleton('admin/session')->isAllowed('kkm_cheque/checkstatus');
        $status             = json_decode($statusModel->getStatus());

        return ($checkStatusAllowed && $status && $status->status == 'wait' && $status->uuid);
    }
}
