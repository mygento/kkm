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
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
    
        $this->_objectManager = $objectManager;
        $this->kkmHelper      = $kkmHelper;
    }

    /**
     * sales_order_creditmemo_save_after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $helper     = $this->kkmHelper;
        $helper->addLog('cancelCheque');
        $creditmemo = $observer->getEvent()->getCreditmemo();
        //Do your stuff here!
        die('Observer Is called!');
    }
}
