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

        $helper->getVendorModel()->sendCheque($invoice, $order);
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

        $helper->getVendorModel()->cancelCheque($creditmemo, $order);
    }

    /**Check and change order's status. if it has failed kkm transactions status should be kkm_failed.
     *If no - status should not be kkm_failed.
     * @param $observer
     */
    public function checkStatus($observer)
    {
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
            Mage::helper('kkm')->addLog("Order {$order->getId()} needs to change its state to {$kkmFailedStatus}");
            $order->setKkmChangeStatusFlag(true);
            $order->setStatus($kkmFailedStatus);

            $order->addStatusHistoryComment('[' . strtoupper($vendorName) . '] '
                                            . Mage::helper('kkm')->__('Status of the order has been changed automatically because it has failed KKM transactions.'));
            $order->save();
        }

        //Change order status if it no longer has failed invoices/creditmemos
        if (!$isKkmFail && $order->getData('status') === $kkmFailedStatus) {
            Mage::helper('kkm')->addLog("Order {$order->getId()} needs to change its state to default status of state {$order->getState()}");
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

    public function addButtonResend($observer)
    {
        $container = $observer->getBlock();
        if (null !== $container && ($container->getType() == 'adminhtml/sales_order_creditmemo_view' || $container->getType() == 'adminhtml/sales_order_invoice_view')) {
            $entity           = $container->getInvoice() ?: $container->getCreditmemo();
            $type             = $entity::HISTORY_ENTITY_NAME;
            $statusExternalId = "{$type}_" . $entity->getIncrementId();
            $statusModel      = Mage::getModel('kkm/status')->load($statusExternalId, 'external_id');
            $status           = json_decode($statusModel->getStatus());

            if (isset($status->status) && $status->status == 'fail') {
                $url = Mage::getModel('adminhtml/url')
                    ->getUrl(
                        'adminhtml/kkm_cheque/resend',
                        [
                            'entity' => $type,
                            'id'     => $entity->getId()
                        ]
                    );
                $data = [
                    'label'   => Mage::helper('kkm')->__('Resend to KKM'),
                    'class'   => '',
                    'onclick' => 'setLocation(\'' . $url . '\')',
                ];

                $container->addButton('resend_to_kkm', $data);
            }
        }

        return $this;
    }
}
