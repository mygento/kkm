<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Model\VendorInterface;

class Send implements ObserverInterface
{
    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\Processor
     */
    private $processor;

    /**
     * @var \Magento\Framework\Message\ManagerInterface\Proxy
     */
    private $messageManager;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * Send constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Model\Processor $processor
     * @param \Magento\Framework\Message\ManagerInterface\Proxy $messageManager
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Model\Processor $processor,
        \Magento\Framework\Message\ManagerInterface\Proxy $messageManager
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->processor = $processor;
        $this->messageManager = $messageManager;
        $this->errorHelper = $errorHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $entity = $observer->getEvent()->getInvoice()
            ?? $observer->getEvent()->getCreditmemo();

        if (!$this->canProceed($entity)) {
            return;
        }

        $this->kkmHelper->info("Auto send {$entity->getEntityType()} to Atol");

        //Set Flag, in order to avoid loop
        $entity->setData(VendorInterface::ALREADY_SENT_FLAG, 1);

        $this->proceed($entity);
    }

    /**
     * Check Invoice|Creditmemo, Kkm setting, Currency etc before sending
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    protected function canProceed($entity)
    {
        if (!$this->kkmHelper->getConfig('general/enabled')
            || !$this->kkmHelper->getConfig('general/auto_send_after_invoice')
            || $entity->getOrderCurrencyCode() != 'RUB'
            || $entity->getData(VendorInterface::ALREADY_SENT_FLAG)
        ) {
            return false;
        }

        $order = $entity->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        $origData = $entity->getOrigData();
        $paymentMethods = $this->kkmHelper->getConfig('general/payment_methods');
        $paymentMethods = explode(',', $paymentMethods);

        if (!in_array($paymentMethod, $paymentMethods)) {
            $this->kkmHelper->debug(
                __(
                    'Skipped autosend %1 %2. Reason: Payment method %3 is not allowed',
                    $entity->getEntityType(),
                    $entity->getIncrementId(),
                    $paymentMethod
                )
            );

            return false;
        }

        if ($origData && isset($origData['increment_id'])) {
            $this->kkmHelper->debug(
                __(
                    'Skipped autosend %1 %2. Reason: %1 is not new',
                    $entity->getEntityType(),
                    $entity->getIncrementId()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     */
    private function proceed($entity)
    {
        try {
            //Send
            $this->send($entity);

            $comment = 'Cheque was placed for sending to KKM.';
            $this->kkmHelper->info(__($comment));
        } catch (\Exception $exc) {
            $this->messageManager->addErrorMessage(
                __(
                    'Cheque has not been successfully registered on KKM vendor side. Reason: %1',
                    $exc->getMessage()
                )
            );

            $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);
        }
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    private function send($entity)
    {
        if ($entity instanceof InvoiceInterface) {
            return $this->processor->proceedSell($entity);
        }
        if ($entity instanceof CreditmemoInterface) {
            return $this->processor->proceedRefund($entity);
        }

        throw new \Exception('Unknown entity to send. ' . get_class($entity));
    }
}
