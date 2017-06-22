<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Plugin;

class addExtraButtons
{

    /** @var \Mygento\Kkm\Helper\Data */
    protected $_kkmHelper;

    /** @var \Mygento\Kkm\Model\StatusFactory */
    protected $_statusFactory;

    /**
     * Role Authorizations Service
     * @var \Magento\Framework\AuthorizationInterface $_authorization
     */
    protected $_authorization;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\StatusFactory $statusFactory,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\UrlInterface $backendUrl
    ) {
    
        $this->_kkmHelper     = $kkmHelper;
        $this->_statusFactory = $statusFactory;
        $this->_authorization = $authorization;
        $this->_backendUrl    = $backendUrl;
    }

    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
    
        if (!$this->isProperPageForResendButton($context)) {
            return;
        }

        $entity         = $context->getInvoice() ?: $context->getCreditmemo();
        $order          = $entity->getOrder();
        $paymentMethod  = $order->getPayment()->getMethod();
        $paymentMethods = explode(
            ',',
            $this->_kkmHelper->getConfig('mygento_kkm/general/payment_methods')
        );

        if (!in_array($paymentMethod, $paymentMethods) || $entity->getOrderCurrencyCode() != 'RUB') {
            return;
        }

        $statusModel = $this->_statusFactory->create()->getCollection()
            ->addFieldToFilter('type', $entity->getEntityType())
            ->addFieldToFilter('increment_id', $entity->getIncrementId())
            ->getFirstItem();

        if ($this->canBeShownResendButton($statusModel)) {
            $url  = $this->_backendUrl->getUrl(
                'kkm/cheque/resend',
                [
                'entity' => $entity->getEntityType(),
                'id'     => $entity->getId()
                ]
            );
            $data = [
                'label'   => __('Resend to KKM'),
                'class'   => '',
                'onclick' => 'setLocation(\'' . $url . '\')',
            ];

            $buttonList->add('resend_to_kkm', $data);
        } elseif ($this->canBeShownCheckStatusButton($statusModel)) {
            $url  = $this->_backendUrl->getUrl(
                'kkm/cheque/checkstatus',
                [
                'uuid' => $statusModel->getUuid()
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
     * @param type $block
     * @return boolean
     */
    protected function isProperPageForResendButton($block)
    {
        return (null !== $block && ($block->getType() == 'Magento\Sales\Block\Adminhtml\Order\Invoice\View' || $block->getType() == 'Magento\Sales\Block\Adminhtml\Order\Creditmemo\View'));
    }

    /**
     * @param \Mygento\Kkm\Model\StatusFactory $statusModel
     * @return boolean
     */
    protected function canBeShownResendButton($statusModel)
    {
        //Check ACL
        $resendAllowed = $this->_authorization->isAllowed('Mygento_Kkm::cheque_resend');
        $status        = $statusModel->getStatus();

        return ($resendAllowed && (!$statusModel->getId() || ($status == 'fail')));
    }

    /**
     * @param \Mygento\Kkm\Model\StatusFactory $statusModel
     * @return boolean
     */
    protected function canBeShownCheckStatusButton($statusModel)
    {
        //Check ACL
        $checkStatusAllowed = $this->_authorization->isAllowed('Mygento_Kkm::cheque_checkstatus');
        $status             = $statusModel->getStatus();

        return ($checkStatusAllowed && $status && $statusModel->getStatus() == 'wait' && $statusModel->getUuid());
    }
}
