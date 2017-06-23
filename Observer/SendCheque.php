<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Observer;

use \Magento\Framework\Event\ObserverInterface;

/**
 * Class SendCheque
 */
class SendCheque implements ObserverInterface
{

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $_objectManager;

    /** @var \Mygento\Kkm\Helper\Data */
    protected $_kkmHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
        $this->_objectManager  = $objectManager;
        $this->_kkmHelper      = $kkmHelper;
    }

    /**
     * sales_order_invoice_save_after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $helper = $this->_kkmHelper;
        $helper->addLog('sendCheque');

        $invoice = $observer->getEvent()->getInvoice();

        if (!$helper->getConfig('mygento_kkm/general/enabled') || !$helper->getConfig('mygento_kkm/general/auto_send_after_invoice') || $invoice->getOrderCurrencyCode() != 'RUB') {
            $helper->addLog('Skipped send cheque.');
            return;
        }

        $order           = $invoice->getOrder();
        $paymentMethod   = $order->getPayment()->getMethod();
        $invoiceOrigData = $invoice->getOrigData();
        $paymentMethods  = explode(',', $helper->getConfig('mygento_kkm/general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod . ' is not allowed for sending cheque.');
            return;
        }

        if ($invoice->getOrigData() && isset($invoiceOrigData['increment_id'])) {
            $helper->addLog('invoice already created');
            return;
        }
        $vendorName = ucfirst($helper->getConfig('mygento_kkm/general/vendor'));

        $sendResult = $this->_objectManager
            ->create('\Mygento\Kkm\Model\Vendor\\' . $vendorName)
            ->sendCheque($invoice, $order);

        if ($sendResult === false) {
            $helper->getMessageManager()->addError(__('Cheque has been rejected by KKM vendor.'));
        }
    }
}
