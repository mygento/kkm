<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\ValidatorException;

class CheckStatus extends \Magento\Backend\App\Action
{
    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * CheckStatus constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Model\VendorInterface $vendor
    ) {
        parent::__construct($context);

        $this->kkmHelper = $helper;
        $this->vendor    = $vendor;
    }

    /**
     * Execute
     */
    public function execute()
    {
        try {
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
