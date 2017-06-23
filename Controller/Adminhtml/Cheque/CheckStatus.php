<?php
/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

/**
 * Class CheckStatus
 */
class CheckStatus extends \Magento\Backend\App\Action
{

    /** @var \Mygento\Kkm\Helper\Data */
    protected $_helper;

    /** @var \Magento\Backend\App\Action\Context */
    protected $_context;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper
    ) {
    
        parent::__construct($context);
        $this->_helper  = $helper;
        $this->_context = $context;
    }

    /**
     * Main action
     */
    public function execute()
    {
        $uuid           = strtolower($this->_request->getParam('uuid'));
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$uuid) {
            $this->getMessageManager()->addError(__('Uuid can not be empty.'));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        $helper     = $this->_helper;
        $vendorName = ucfirst($this->_helper->getConfig('mygento_kkm/general/vendor'));
        $vendor     = $this->_context->getObjectManager()->create('\Mygento\Kkm\Model\Vendor\\' . $vendorName);

        $result = $vendor->checkStatus($uuid);

        if (!$result) {
            $this->getMessageManager()->addError(__('Can not check status of the transaction.'));
        } else {
            $this->getMessageManager()->addSuccess(__('Check Status was updated.'));
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
