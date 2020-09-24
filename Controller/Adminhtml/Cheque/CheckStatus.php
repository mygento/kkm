<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

class CheckStatus extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_checkstatus';

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
        $this->vendor = $vendor;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $uuid = strtolower($this->_request->getParam('uuid'));
        if (!$uuid) {
            $this->getMessageManager()->addErrorMessage(__('Invalid request. No uuid specified.'));
            $this->kkmHelper->error(
                'Invalid url. No uuid. Params:',
                $this->getRequest()->getParams()
            );

            return $this->redirect();
        }

        $uuids = explode(',', $uuid);

        foreach ($uuids as $value) {
            $this->check($value);
        }

        return $this->redirect();
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function redirect(): \Magento\Framework\Controller\Result\Redirect
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->_redirect->getRefererUrl()
        );
    }

    /**
     * @param string $uuid
     */
    protected function check(string $uuid): void
    {
        try {
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
        }
    }
}
