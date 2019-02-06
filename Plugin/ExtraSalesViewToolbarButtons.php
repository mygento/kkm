<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
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
     * @var \Magento\Framework\AuthorizationInterface $_authorization
     */
    protected $_authorization;

    /** @var \Magento\Backend\Model\UrlInterface */
    protected $urlBuilder;
    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * Constructor
     *
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->kkmHelper     = $kkmHelper;
        $this->_authorization = $authorization;
        $this->urlBuilder    = $urlBuilder;
        $this->transactionHelper = $transactionHelper;
    }

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

        if (!in_array($paymentMethod, $paymentMethods) || $entity->getOrderCurrencyCode() != 'RUB') {
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
                'onclick' => 'setLocation(\''.$url.'\')',
            ];
            $buttonList->add('check_status_in_kkm', $data);
        }
    }

    /**
     * Check is current page appropriate for "resend to kkm" button
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Invoice\View | \Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $block
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
        $resendAllowed = $this->_authorization->isAllowed('Mygento_Kkm::cheque_resend');

        return $resendAllowed;
    }

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
        $checkStatusAllowed = $this->_authorization->isAllowed('Mygento_Kkm::cheque_checkstatus');

        return $checkStatusAllowed && $isWait;
    }
}