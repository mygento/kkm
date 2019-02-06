<?php
/**
 * @author Mygento
 * @copyright Copyright 2017 Mygento
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\ValidatorException;

class CheckStatus extends \Magento\Backend\App\Action
{
    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;
    /**
     * @var \Mygento\Kkm\Model\Atol\Vendor
     */
    private $vendor;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Model\Atol\Vendor $vendor
    ) {
        parent::__construct($context);

        $this->kkmHelper = $helper;
        $this->vendor    = $vendor;
    }

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
            $this->kkmHelper->error('Invalid url. No uuid. Params: ');
            $this->kkmHelper->error($this->getRequest()->getParams());

            throw new ValidatorException(__('Invalid request. Check logs.'));
        }
    }
}