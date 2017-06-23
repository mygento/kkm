<?php
/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

/**
 * Class Resend
 */
class Resend extends \Magento\Backend\App\Action
{

    /** @var \Mygento\Kkm\Helper\Data */
    protected $_helper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
    ) {
    
        parent::__construct($context);
        $this->_helper               = $helper;
        $this->_context              = $context;
        $this->_orderInvoiceFactory  = $orderInvoiceFactory;
        $this->_creditmemoRepository = $creditmemoRepository;
    }

    /**
     * Main action
     */
    public function execute()
    {

        $entityType = strtolower($this->_request->getParam('entity'));
        $id         = $this->_request->getParam('id');

        $helper     = $this->_helper;
        $vendorName = ucfirst($this->_helper->getConfig('mygento_kkm/general/vendor'));
        $vendor     = $this->_context->getObjectManager()->create('\Mygento\Kkm\Model\Vendor\\' . $vendorName);
        
        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            $this->getMessageManager()->addError(__('Something goes wrong. Check logs.'));
            $helper->addLog('Invalid url. No id or invalid entity type. Params: ', \Zend\Log\Logger::ERR);
            $helper->addLog($this->getRequest()->getParams(), \Zend\Log\Logger::ERR);
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }
        
        if ($entityType === 'invoice') {
            $entity = $this->_orderInvoiceFactory->create()->load($id);
        } elseif ($entityType === 'creditmemo') {
            $entity = $this->_creditmemoRepository->get($id);
        }
        
        if (!$entity->getId()) {
            $this->getMessageManager()->addError(__('Something goes wrong. Check log file.'));
            $helper->addLog('Entity with Id from request does not exist. Id: ' . $id, \Zend\Log\Logger::ERR);
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }
        
        $method = 'sendCheque';
        
        if ($entityType == 'creditmemo') {
            $method  = 'cancelCheque';
            $comment = 'Refund was sent to KKM. Status of the transaction see in orders comment.';
        } else {
            $comment = 'Cheque was sent to KKM. Status of the transaction see in orders comment.';
        }
        
        $vendor->$method($entity, $entity->getOrder());
        
        $this->getMessageManager()->addSuccess(__($comment));
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
    }
}
