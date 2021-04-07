<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Mygento\Kkm\Model\VendorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Send implements ObserverInterface
{
    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
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
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Magento\Framework\Message\ManagerInterface\Proxy $messageManager
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Api\Processor\SendInterface $processor,
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

        $vendorCode = $this->kkmHelper->getCurrentVendorCode($entity->getStoreId());
        $this->kkmHelper->info("Auto send {$entity->getEntityType()} to {$vendorCode}");

        //Set Flag, in order to avoid loop
        $entity->setData(VendorInterface::ALREADY_SENT_FLAG, 1);

        $this->proceed($entity);
    }

    /**
     * Check Invoice|Creditmemo, Kkm setting, Currency etc before sending
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function canProceed($entity)
    {
        if (!$this->kkmHelper->getConfig('general/enabled')
            || !$this->kkmHelper->getConfig('general/auto_send_after_invoice')
            || $entity->getOrderCurrencyCode() != 'RUB'
            || $this->isAlreadySent($entity)
            || !$this->isStateAllowed($entity)
        ) {
            return false;
        }

        if (!$entity->getData(VendorInterface::SKIP_PAYMENT_METHOD_VALIDATION)) {
            $order = $entity->getOrder();
            $paymentMethod = $order->getPayment()->getMethod();
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
        }

        $origData = $entity->getOrigData();
        if ($origData && isset($origData['state']) && ($origData['state'] === $entity->getState())) {
            $this->kkmHelper->debug(
                __(
                    'Skipped autosend %1 %2. Reason: %1 is not new or state not changed',
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
     * @return bool
     */
    private function isAlreadySent($entity)
    {
        return $entity->getData(VendorInterface::ALREADY_SENT_FLAG)
            || ($entity->getOrder() ? $entity->getOrder()->getData(VendorInterface::ALREADY_SENT_FLAG) : false);
    }

    /**
     * @param Creditmemo|Invoice $entity
     * @return bool
     */
    private function isStateAllowed($entity)
    {
        $allowedState = $entity->getEntityType() === 'invoice'
            ? Invoice::STATE_PAID
            : Creditmemo::STATE_REFUNDED;
        if ($entity->getState() != $allowedState) {
            $this->kkmHelper->debug(
                __(
                    'Wrong state for autosending. %1 %2 in %3 state.',
                    $entity->getEntityType(),
                    $entity->getIncrementId(),
                    $entity->getStateName()
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
        } catch (\Throwable $thr) {
            $this->messageManager->addErrorMessage(
                __('Cheque has not been successfully registered on KKM vendor side. See log.')
            );
            $this->kkmHelper->error('Resend failed. Reason: ' . $thr->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($entity, $thr);
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
