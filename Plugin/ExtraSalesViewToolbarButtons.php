<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Plugin;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Exception\ResendAvailabilityException;
use Mygento\Kkm\Model\Atol\Response;

class ExtraSalesViewToolbarButtons
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Model\Resend\ValidatorInterface
     */
    private $resendValidator;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param \Mygento\Kkm\Model\Resend\ValidatorInterface $resendValidator
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Mygento\Kkm\Model\Resend\ValidatorInterface $resendValidator
    ) {
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->transactionHelper = $transactionHelper;
        $this->resendValidator = $resendValidator;
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

        $entity = $context->getInvoice() ?: $context->getCreditmemo();

        $transactions = $this->transactionHelper->getTransactionsByEntity($entity, true);

        if ($this->canBeShownResendButton($entity)) {
            $url = $this->urlBuilder->getUrl(
                'kkm/cheque/resend',
                [
                    'entity' => $entity->getEntityType(),
                    'id' => $entity->getId(),
                    'store_id' => $entity->getStoreId(),
                ]
            );
            $data = [
                'label' => __('Send to KKM'),
                'class' => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $buttonList->add('resend_to_kkm', $data);
        } elseif ($this->canBeShownCheckStatusButton($transactions)) {
            $url = $this->urlBuilder->getUrl(
                'kkm/cheque/checkStatus',
                [
                    'uuid' => implode(',', $this->transactionHelper->getWaitUuid($entity)),
                    'store_id' => $entity->getStoreId(),
                ]
            );
            $data = [
                'label' => __('Check status in KKM'),
                'class' => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];
            $buttonList->add('check_status_in_kkm', $data);
        } elseif ($entity instanceof InvoiceInterface && $this->canBeShownResellButton($transactions)) {
            $url = $this->urlBuilder->getUrl(
                'kkm/cheque/resell',
                [
                    'id' => $entity->getId(),
                ]
            );
            $data = [
                'label' => __('Send Resell to KKM'),
                'class' => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $buttonList->add('resell_to_kkm', $data);
        }

        if ($this->isVisibleResendWithIncrExtIdButton($transactions)) {
            $url = $this->urlBuilder->getUrl(
                'kkm/cheque/resend',
                [
                    'entity' => $entity->getEntityType(),
                    'id' => $entity->getId(),
                    'store_id' => $entity->getStoreId(),
                    'incr_ext_id' => true,
                ]
            );
            $data = [
                'label' => __('Send to KKM with incr ext id'),
                'class' => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $buttonList->add('resend_to_kkm_with_incr_ext_id', $data);
        }
    }

    /**
     * Check is current page appropriate for "resend to kkm" button
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return bool
     */
    private function isProperPageForKkmButtons($block)
    {
        return null !== $block && (
            strpos($block->getType(), 'Adminhtml\Order\Invoice\View')
            || strpos($block->getType(), 'Adminhtml\Order\Creditmemo\View')
        );
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    private function canBeShownResendButton($entity)
    {
        try {
            $this->resendValidator->validate($entity);
        } catch (ResendAvailabilityException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param array $transactions
     * @return bool
     */
    private function canBeShownResellButton($transactions)
    {
        //Если есть Done. Только для Invoice.
        $isDone = false;
        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            if ($status === Response::STATUS_DONE) {
                $isDone = true;
            }
        }

        //Check ACL
        return $isDone && $this->authorization->isAllowed('Mygento_Kkm::cheque_resend');
    }

    /**
     * @param array $transactions
     * @return bool
     */
    private function isVisibleResendWithIncrExtIdButton($transactions)
    {
        //Check ACL
        return false === empty($transactions) &&
            $this->authorization->isAllowed('Mygento_Kkm::cheque_resend_with_incr_ext_id');
    }

    /**
     * @param array $transactions
     * @return bool
     */
    private function canBeShownCheckStatusButton($transactions)
    {
        // Если есть Done || нет Wait - то нельзя спросить статус
        $isWait = false;
        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            // может быть завршенная транзакция по предоплате
//            if ($status === Response::STATUS_DONE) {
//                return false;
//            }
            if ($status === Response::STATUS_WAIT) {
                $isWait = true;
            }
        }

        //Check ACL
        $checkStatusAllowed = $this->authorization->isAllowed('Mygento_Kkm::cheque_checkstatus');

        return $checkStatusAllowed && $isWait;
    }
}
