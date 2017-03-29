<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
abstract class Mygento_Kkm_Model_Observer
{

    /**
     * 
     * @param type $observer
     */
    public function sendCheque($observer)
    {

        if (!$this->helper('kkm')->getConfig('enabled') && !$this->helper('kkm')->getConfig('auto_send_after_invoice')) {
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();

        Mage::getModel('kkm/vendor_' . $this->helper('kkm')->getConfig('vendor'))->sendCheque($invoice);
    }

    /**
     * 
     * @param type $observer
     */
    public function cancelCheque($observer)
    {
        if (!$this->helper('kkm')->getConfig('enabled') && !$this->helper('kkm')->getConfig('auto_send_after_cancel')) {
            return;
        }

        $order = $observer->getOrder();

        if ($order->hasInvoices()) {
            Mage::getModel('kkm/vendor_' . $this->helper('kkm')->getConfig('vendor'))->cancelCheque($order);
        }
    }
}
