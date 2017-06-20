<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Observer;

use \Magento\Framework\Event\ObserverInterface;

class CancelCheque implements ObserverInterface
{

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Kkm helper
     */
    protected $_kkmHelper;

    /**
     *
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_objectManager  = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_kkmHelper      = $kkmHelper;
    }

    /**
     * sales_order_creditmemo_save_after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $helper     = $this->_kkmHelper;
        $helper->addLog('cancelCheque');
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if (!$helper->getConfig('mygento_kkm/general/enabled') || !$helper->getConfig('mygento_kkm/general/auto_send_after_cancel') || $creditmemo->getOrderCurrencyCode() !== 'RUB') {
            $helper->addLog('Skipped cancel cheque.');
            return;
        }

        $order              = $creditmemo->getOrder();
        $paymentMethod      = $order->getPayment()->getMethod();
        $creditmemoOrigData = $creditmemo->getOrigData();
        $paymentMethods     = explode(',', $helper->getConfig('mygento_kkm/general/payment_methods'));

        if (!in_array($paymentMethod, $paymentMethods)) {
            $helper->addLog('paymentMethod: ' . $paymentMethod . ' is not allowed for cancelling cheque.');
            return;
        }

        if ($creditmemo->getOrigData() && isset($creditmemoOrigData['increment_id'])) {
            return;
        }

        $vendorName = ucfirst($helper->getConfig('mygento_kkm/general/vendor'));

        $sendResult = $this->_objectManager
            ->create('\Mygento\Kkm\Model\Vendor\\' . $vendorName)
            ->cancelCheque($creditmemo, $order);

        if ($sendResult === false) {
            $this->_messageManager->addError(__('Cheque has been rejected by KKM vendor.'));
        }
    }
}
