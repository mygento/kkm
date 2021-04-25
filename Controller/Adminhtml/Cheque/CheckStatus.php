<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Store\Model\App\Emulation;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Helper\Data;

class CheckStatus extends Action
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
     * @var \Mygento\Kkm\Api\Processor\UpdateInterface
     */
    private $updateProcessor;

    /**
     * CheckStatus constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        UpdateInterface $updateProcessor,
        Emulation $emulation,
        Data $helper
    ) {
        parent::__construct($context);

        $this->kkmHelper = $helper;
        $this->updateProcessor = $updateProcessor;
        $this->emulation = $emulation;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $storeId = $this->_request->getParam('store_id');
        $uuid = strtolower($this->_request->getParam('uuid'));

        if (!$this->kkmHelper->isVendorNeedUpdateStatus($storeId)) {
            return $this->exitWithoutUpdateStatus($this->kkmHelper->getCurrentVendorCode($storeId));
        }

        if (!$uuid) {
            $this->getMessageManager()->addErrorMessage(__('Invalid request. No uuid specified.'));
            $this->kkmHelper->error(
                'Invalid url. No uuid. Params:',
                $this->getRequest()->getParams()
            );

            return $this->redirect();
        }

        $this->emulation->startEnvironmentEmulation($storeId);

        $uuids = explode(',', $uuid);

        foreach ($uuids as $value) {
            $this->check($value);
        }

        $this->emulation->stopEnvironmentEmulation();

        return $this->redirect();
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function redirect(): Redirect
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
            $response = $this->updateProcessor->proceedSync($uuid);

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

    /**
     * @param string $currentVendorCode
     * @return Redirect
     */
    private function exitWithoutUpdateStatus($currentVendorCode)
    {
        $this->getMessageManager()->addNoticeMessage(
            __('Current vendor "%1" does not need update status.', $currentVendorCode)
        );

        return $this->redirect();
    }
}
