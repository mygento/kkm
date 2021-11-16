<?php

namespace Mygento\Kkm\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class MarkAttemptAsDone implements ObserverInterface
{
    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $transactionAttemptHelper;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @param \Mygento\Kkm\Helper\TransactionAttempt $transactionAttemptHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Mygento\Kkm\Helper\TransactionAttempt $transactionAttemptHelper,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
        $this->transactionAttemptHelper = $transactionAttemptHelper;
        $this->kkmHelper = $kkmHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $entity = $observer->getEvent()->getEntity();
            $this->transactionAttemptHelper->markEntityAttemptAsDone($entity);
        } catch (\Exception $e) {
            $this->kkmHelper->error($e->getMessage());
        }
    }
}