<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\ValidatorException;

class CheckStatus extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_checkstatus';

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->kkmHelper = $helper;
        $this->vendor = $vendor;
        $this->emulation = $emulation;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $storeId = $this->_request->getParam('store_id');

        try {
            $this->emulation->startEnvironmentEmulation($storeId);
            $this->validateRequest();
            $uuid = strtolower($this->_request->getParam('uuid'));
            $response = $this->vendor->updateStatus($uuid);
            $this->getMessageManager()->addSuccessMessage(
                __('Kkm transaction status was updated. Status: %1', $response->getStatus())
            );
        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage(
                __('Can not check status of the transaction.')
            );
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
            $this->kkmHelper->error($exc->getMessage());
        } finally {
            $this->emulation->stopEnvironmentEmulation();

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->getRefererUrl()
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function validateRequest()
    {
        $uuid = $this->getRequest()->getParam('uuid');

        if (!$uuid) {
            $this->kkmHelper->error(
                'Invalid url. No uuid. Params:',
                $this->getRequest()->getParams()
            );

            throw new ValidatorException(__('Invalid request. Check logs.'));
        }
    }
}
