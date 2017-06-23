<?php
/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

class CheckStatus extends \Magento\Backend\App\Action
{

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $_helper;

    public function __construct(
    \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        parent::__construct($context);
        $this->_objectManager = $objectManager;
        $this->_helper        = $helper;
    }

    /**
     * Main action
     */
    public function execute()
    {
        $uuid       = strtolower($this->_request->getParam('uuid'));
        $helper     = $this->_helper;
        $vendorName = ucfirst($this->_helper->getConfig('mygento_kkm/general/vendor'));

        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$uuid) {
            $this->getMessageManager()->addError(__('Uuid can not be empty.'));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

//        if (!$vendor) {
//            $this->getMessageManager()->addError(__('KKM Vendor not found.') . ' ' . __('Check KKM module settings.'));
//            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
//        }

        $sendResult = $this->_objectManager
            ->create('\Mygento\Kkm\Model\Vendor\\' . $vendorName)
            ->checkStatus($uuid);

        if (!$result) {
            $this->getMessageManager()->addError(__('Can not check status of the transaction.'));
        } else {
            $this->getMessageManager()->addSuccess(__('Check Status was updated.'));
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }

}
