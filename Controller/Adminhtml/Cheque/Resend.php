<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;

class Resend extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_resend';

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Api\ResenderInterface
     */
    private $resender;

    /**
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mygento\Kkm\Api\ResenderInterface $resender
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Api\ResenderInterface $resender
    ) {
        parent::__construct($context);

        $this->kkmHelper = $kkmHelper;
        $this->emulation = $emulation;
        $this->resender = $resender;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $storeId = $this->_request->getParam('store_id');

        try {
            $this->validateRequest();
            $this->emulation->startEnvironmentEmulation($storeId);

            $entityType = strtolower($this->_request->getParam('entity'));
            $id = $this->getRequest()->getParam('id');
            $incrExtId = (bool)$this->getRequest()->getParam('incr_ext_id');

            $this->resender->resend($id, $entityType, $incrExtId);

            $comment = 'Cheque' . $this->kkmHelper->isMessageQueueEnabled($storeId)
                ? 'was placed to queue for further sending.'
                : 'was sent to KKM.';

            $this->getMessageManager()->addSuccessMessage(__($comment));
        } catch (NoSuchEntityException $exc) {
            $this->getMessageManager()->addErrorMessage(
                __(ucfirst($entityType)) . " {$id} " . __('not found')
            );
        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
        } catch (\Throwable $thr) {
            $this->getMessageManager()->addErrorMessage(__('Something went wrong. See log.'));
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
        $entityType = $this->getRequest()->getParam('entity');
        $id = $this->getRequest()->getParam('id');

        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            $this->kkmHelper->error(
                'Invalid url. No id or invalid entity type.Params:',
                $this->getRequest()->getParams()
            );

            throw new ValidatorException(__('Invalid request. Check logs.'));
        }
    }
}
