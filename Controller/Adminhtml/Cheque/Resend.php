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

    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Helper\Error
     */
    private $errorHelper;

    /**
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error $errorHelper
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error $errorHelper,
        \Mygento\Kkm\Api\Processor\SendInterface $processor,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);

        $this->kkmHelper = $kkmHelper;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->processor = $processor;
        $this->errorHelper = $errorHelper;
        $this->emulation = $emulation;
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

            $incrExtId = (bool) $this->getRequest()->getParam('incr_ext_id');

            switch ($entityType) {
                case 'invoice':
                    $entity = $this->invoiceRepository->get($id);
                    $this->processor->proceedSell($entity, true, true, $incrExtId);
                    $comment = 'Cheque ';
                    break;
                case 'creditmemo':
                    $entity = $this->creditmemoRepository->get($id);
                    $this->processor->proceedRefund($entity, true, true, $incrExtId);
                    $comment = 'Refund ';
                    break;
            }

            $comment .= $this->kkmHelper->isMessageQueueEnabled($storeId)
                ? 'was placed to queue for further sending.'
                : 'was sent to KKM.';

            $this->getMessageManager()->addSuccessMessage(__($comment));
        } catch (NoSuchEntityException $exc) {
            $this->getMessageManager()->addErrorMessage(
                __(ucfirst($entityType)) . " {$id} " . __('not found')
            );
            $this->kkmHelper->error("Entity {$entityType} with Id {$id} not found.");
        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
            $this->kkmHelper->error('Resend failed. Reason: ' . $exc->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);
        } catch (\Throwable $thr) {
            $this->getMessageManager()->addErrorMessage(__('Something went wrong. See log.'));
            $this->kkmHelper->error('Resend failed. Reason: ' . $thr->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($entity, $thr);
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
