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

        $invoice = $observer->getEvent()->getInvoice();
        $order = $observer->getEvent()->getOrder();

//        Mage::log(print_r($invoice->getData(),1),null,'invoice_data.log');
//        Mage::log(print_r($order->getData(),1),null,'order_data.log');
        
        $items = $invoice->getAllItems();
        
        foreach ($items as $item) {
//            Mage::log(print_r($item->getData(),1),null,'items_data.log');
        }

//        $invoice->getAllItems();
        $sendCheque = Mage::getModel('kkm/vendor_' . Mage::helper('kkm')->getConfig('general/vendor'))->sendCheque($invoice, $order);
        Mage::log(print_r($sendCheque,1),null,'data_to_send.log');
        
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

        $creditmemo = $observer->getEvent()->getCreditmemo();
        Mage::helper('kkm')->addLog($creditmemo->getData());

//        if ($order->hasInvoices()) {
//            Mage::getModel('kkm/vendor_' . Mage::helper('kkm')->getConfig('general/vendor'))->cancelCheque($order);
//        }
    }
}
