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
        $helper  = Mage::helper('kkm');
        $invoice = $observer->getEvent()->getInvoice();

        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_invoice')) {
            return;
        }

        $order = $invoice->getOrder();

        //Проверяем флаг, чтобы предотвратить повторную отправку
        if ($invoice->getSendCheque()) {
            return;
        }

        $invoiceOrigData = $invoice->getOrigData();

        if ($invoice->getOrigData() && isset($invoiceOrigData['increment_id'])) {
            return;
        }

        if ($helper->skipCheque($order)) {
            $helper->addLog('Skipped send cheque. Payment method: ' . $order->getPayment()->getMethod()
                            . ', currency: ' . $order->getOrderCurrencyCode());
            return;
        }

        $helper->addLog('sendCheque ' . $invoice->getData('increment_id'));
        try {
            //Сетим флаг, чтобы предотвратить повторную отправку
            $invoice->setSendCheque(1);
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

        $creditmemo = $observer->getEvent()->getCreditmemo();
        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/auto_send_after_cancel')) {
            return;
        }

        $order = $creditmemo->getOrder();

        //Проверяем флаг, чтобы предотвратить повторную отправку
        if ($creditmemo->getCancelCheque()) {
            return;
        }

        $creditmemoOrigData = $creditmemo->getOrigData();

        if ($helper->skipCheque($order)) {
            $helper->addLog('Skipped cancel cheque. Payment method: ' . $order->getPayment()->getMethod()
                            . ', currency: ' . $order->getOrderCurrencyCode());
            return;
        }

        if ($creditmemo->getOrigData() && isset($creditmemoOrigData['increment_id'])) {
            return;
        }

        $helper->addLog('cancelCheque');
        try {
            //Сетим флаг, чтобы предотвратить повторную отправку
            $creditmemo->setCancelCheque(1);
            $helper->getVendorModel()->cancelCheque($creditmemo, $order);
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
        }
    }

    /** Check and change order's status. if it has failed kkm transactions status should be kkm_failed.
     *  If no - status should not be kkm_failed.
     * @param $observer
     */
    public function checkStatus($observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (Mage::helper('kkm')->skipCheque($order)) {
            return;
        }
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function updateTransactions()
    {
        $helper        = Mage::helper('kkm');
        $vendor        = $helper->getVendorModel();
        $autoSendLimit = $helper->getConfig('autosend_limit');

        if (!$helper->getConfig('enabled')) {
            return;
        }

        $waitStatuses = Mage::getModel('kkm/status')->getCollection()
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

            if (!$entity->getId()) {
                continue;
            }

            try {
                $vendor->processExistingTransactionBeforeSending($failStatus);
                $vendor->$method($entity);

                $failUpdated++;
            } catch (Mygento_Kkm_SendingException $e) {
                $helper->processError($e);

                $failUpdated++;
            } catch (Exception $e) {
                $debug = json_encode($failStatus->getData(), JSON_UNESCAPED_UNICODE);

                $helper->addLog($e->getMessage() . ' Status object: ' . $debug, Zend_Log::ERR);
            }
        }

        $helper->addLog(
            "{$waitUpdated} records with status 'wait' were successfully updated by CRON.",
            Zend_Log::WARN
        );
        $helper->addLog(
            "{$failUpdated} records with status 'fail' were successfully resent or updated by CRON.",
            Zend_Log::WARN
        );
    }

    public function addExtraButtons($observer)
    {
        if (!Mage::helper('kkm')->getConfig('general/enabled')) {
            return;
        }

        $container = $observer->getBlock();

        //Add Download KKM json button
        $this->addJsonButton($observer);

        if (!$this->isProperPageForResendButton($container)) {
            return;
        }

        $entity = $container->getInvoice() ?: $container->getCreditmemo();
        $order  = $entity->getOrder();

        if (Mage::helper('kkm')->skipCheque($order)) {
            return;
        }

        $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);
        $status      = json_decode($statusModel->getStatus());

        $this->addPhpUnitTestButton($observer);

        //Add ReSend to KKM button
        if ($this->canBeShownResendButton($statusModel)) {
            $this->addResendButton($observer);

            return $this;
        }

        //Add Check Status button
        if ($this->canBeShownCheckStatusButton($statusModel)) {
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

    public function addResendButton($observer)
    {
        $container = $observer->getBlock();
        $entity    = $container->getInvoice() ?: $container->getCreditmemo();

        $url = Mage::getModel('adminhtml/url')
                ->getUrl(
                    'adminhtml/kkm_cheque/resend',
                    [
                    'entity' => $entity::HISTORY_ENTITY_NAME,
                    'id'     => $entity->getId()
                    ]
                );
        $urlEnforce = Mage::getModel('adminhtml/url')
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
    }

    public function addJsonButton($observer)
    {
        $block = $observer->getBlock();
        $order = $block->getOrder();

        $jsonButtonEnabled = Mage::helper('kkm')->getConfig('json_button');

        if (!$order || !$jsonButtonEnabled || $block->getType() != 'adminhtml/sales_order_view') {
            return;
        }

        $url  = Mage::getModel('adminhtml/url')
                ->getUrl('adminhtml/kkm_cheque/getjson', ['id' => $order->getIncrementId()]);
        $data = [
            'label'   => Mage::helper('kkm')->__('Download KKM json'),
            'class'   => '',
            'onclick' => 'setLocation(\'' . $url . '\')',
        ];

        $block->addButton('json_to_kkm', $data);
    }

    public function addPhpUnitTestButton($observer)
    {
        $block  = $observer->getBlock();
        $entity = $block->getInvoice() ?: $block->getCreditmemo();

        $unitTestButtonEnabled = Mage::helper('kkm')->getConfig('unit_test_button');

        if (!$entity || !$entity->getId() || !$unitTestButtonEnabled) {
            return;
        }

        $url = Mage::getModel('adminhtml/url')
            ->getUrl(
                'adminhtml/kkm_cheque/getunittest',
                [
                    'entity' => $entity::HISTORY_ENTITY_NAME,
                    'id'     => $entity->getId()
                ]
            );

        $data = [
            'label'   => Mage::helper('kkm')->__('Download test data'),
            'class'   => '',
            'onclick' => 'setLocation(\'' . $url . '\')',
        ];

        $block->addButton('phpunit_data', $data);
    }

    /** Check is current page appropriate for "resend to kkm" button
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
