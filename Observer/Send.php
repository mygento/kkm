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
use Mygento\Kkm\Model\Atol\Vendor;

class Send implements ObserverInterface
{
    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;
    /**
     * @var \Mygento\Kkm\Model\Atol\Vendor
     */
    private $vendor;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\Atol\Vendor $vendor
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->vendor = $vendor;
    }

    /**
     * sales_order_invoice_save_commit_after event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $entity = $observer->getEvent()->getInvoice()
                ?? $observer->getEvent()->getCreditmemo();

            if (!$this->canProceed($entity)) {
                return;
            }

            $this->kkmHelper->info("Auto send {$entity->getEntityType()} to Atol");

            //Set Flag, in order to avoid loop
            $entity->setData(Vendor::ALREADY_SENT_FLAG, 1);

            //Send
            $response = $this->vendor->send($entity);

            $comment = 'Cheque was sent to KKM. Status: %1';
            $this->kkmHelper->getMessageManager()->addSuccessMessage(
                __($comment, $response->getStatus())
            );
            $this->kkmHelper->info(__($comment, $response->getStatus()));
        } catch (\Exception $exc) {
            $this->kkmHelper->getMessageManager()->addErrorMessage(
                __(
                    'Cheque has not been successfully registered on KKM vendor side. Reason: %1',
                    $exc->getMessage()
                )
            );

            $this->kkmHelper->processKkmChequeRegistrationError($entity, $exc);
        }
    }

    /** Check Invoice|Creditmemo, Kkm setting, Currency etc before sending
     *
     * @param InvoiceInterface|CreditmemoInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool
     */
    protected function canProceed($entity)
    {
        if (!$this->kkmHelper->getConfig('general/enabled')
            || !$this->kkmHelper->getConfig('general/auto_send_after_invoice')
            || $entity->getOrderCurrencyCode() != 'RUB'
            || $entity->getData(Vendor::ALREADY_SENT_FLAG)
        ) {
            return false;
        }

        $order          = $entity->getOrder();
        $paymentMethod  = $order->getPayment()->getMethod();
        $origData       = $entity->getOrigData();
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
}
