<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Plugin;

use Mygento\Kkm\Model\Atol\Response;

class ExtraSalesViewToolbarButtons
{

    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;

    /**
     * Role Authorizations Service
     * @var \Magento\Framework\AuthorizationInterface $authorization
     */
    protected $authorization;

    /** @var \Magento\Backend\Model\UrlInterface */
    protected $urlBuilder;
    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * ExtraSalesViewToolbarButtons constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->kkmHelper         = $kkmHelper;
        $this->authorization     = $authorization;
        $this->urlBuilder        = $urlBuilder;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$this->isProperPageForKkmButtons($context)) {
            return;
        }

        $entity         = $context->getInvoice() ?: $context->getCreditmemo();
        $order          = $entity->getOrder();
        $paymentMethod  = $order->getPayment()->getMethod();
        $paymentMethods = explode(
            ',',
            $this->kkmHelper->getConfig('general/payment_methods')
        );

        if (!in_array($paymentMethod, $paymentMethods)
            || $entity->getOrderCurrencyCode() != 'RUB'
        ) {
            return;
        }

        $transactions = $this->transactionHelper->getTransactionsByEntity($entity);

        if ($this->canBeShownResendButton($transactions)) {
            $url  = $this->urlBuilder->getUrl(
                'kkm/cheque/resend',
                [
                'entity' => $entity->getEntityType(),
                'id'     => $entity->getId()
                ]
            );
            $data = [
                'label'   => __('Send to KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $buttonList->add('resend_to_kkm', $data);
        } elseif ($this->canBeShownCheckStatusButton($transactions)) {
            $url  = $this->urlBuilder->getUrl(
                'kkm/cheque/checkStatus',
                [
                    'uuid' => $this->transactionHelper->getWaitUuid($entity),
                ]
            );
            $data = [
                'label'   => __('Check status in KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];
            $buttonList->add('check_status_in_kkm', $data);
        }
    }

    /**
     * Check is current page appropriate for "resend to kkm" button
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return boolean
     */
    protected function isProperPageForKkmButtons($block)
    {
        return (null !== $block && (
            strpos($block->getType(), 'Adminhtml\Order\Invoice\View')
                ||
                strpos($block->getType(), 'Adminhtml\Order\Creditmemo\View')
            )
        );
    }

    /**
     * @param array $transactions
     * @return bool
     */
    protected function canBeShownResendButton($transactions)
    {
        //Есть ли хоть одна Done || Wait - то нельзя отправить снова
        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            if ($status === Response::STATUS_DONE || $status === Response::STATUS_WAIT) {
                return false;
            }
        }

        //Check ACL
        $resendAllowed = $this->authorization->isAllowed('Mygento_Kkm::cheque_resend');

        return $resendAllowed;
    }

    /**
     * @param array $transactions
     * @return bool
     */
    protected function canBeShownCheckStatusButton($transactions)
    {
        //Есть ли есть Done || нет Wait - то нельзя спросить статус
        $isWait = false;
        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            if ($status === Response::STATUS_DONE) {
                return false;
            }
            if ($status === Response::STATUS_WAIT) {
                $isWait = true;
            }
        }

        //Check ACL
        $checkStatusAllowed = $this->authorization->isAllowed('Mygento_Kkm::cheque_checkstatus');

        return $checkStatusAllowed && $isWait;
    }
}
