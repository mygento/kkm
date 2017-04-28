<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Model_Observer
{
    /**
     *
     * @param type $observer
     */
    public function sendCheque($observer)
    {
        Mage::helper('kkm')->addLog('sendCheque');
        if (!Mage::helper('kkm')->getConfig('general/enabled') && !Mage::helper('kkm')->getConfig('general/auto_send_after_invoice')) {
            return;
        }
        $paymentMethods = explode(',', Mage::helper('kkm')->getConfig('general/payment_methods'));

        $invoice         = $observer->getEvent()->getInvoice();
        $order           = $invoice->getOrder();
        $paymentMethod   = $order->getPayment()->getMethod();
        $invoiceOrigData = $invoice->getOrigData();

        if (!in_array($paymentMethod, $paymentMethods)) {
            Mage::helper('kkm')->addLog('paymentMethod: ' . $paymentMethod);
            return;
        }

        if ($invoice->getOrigData() && isset($invoiceOrigData['increment_id'])) {
            return;
        }

        $sendCheque = Mage::getModel('kkm/vendor_' . Mage::helper('kkm')
                    ->getConfig('general/vendor'))->sendCheque($invoice, $order);
    }

    /**
     *
     * @param type $observer
     */
    public function cancelCheque($observer)
    {
        Mage::helper('kkm')->addLog('cancelCheque');
        if (!Mage::helper('kkm')->getConfig('general/enabled') && !Mage::helper('kkm')->getConfig('general/auto_send_after_cancel')) {
            return;
        }

        $creditmemo         = $observer->getEvent()->getCreditmemo();
        $order              = $creditmemo->getOrder();
        $paymentMethod      = $order->getPayment()->getMethod();
        $creditmemoOrigData = $creditmemo->getOrigData();
        $paymentMethods     = explode(',', Mage::helper('kkm')->getConfig('general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            Mage::helper('kkm')->addLog('paymentMethod: ' . $paymentMethod);
            return;
        }
        if ($creditmemo->getOrigData() && isset($creditmemoOrigData['increment_id'])) {
            return;
        }

        $cancelCheque = Mage::getModel('kkm/vendor_' . Mage::helper('kkm')
                    ->getConfig('general/vendor'))->cancelCheque($creditmemo, $order);
    }
}
