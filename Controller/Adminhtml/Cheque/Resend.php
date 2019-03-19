<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;

class Resend extends \Magento\Backend\App\Action
{
    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var \Mygento\Kkm\Model\Processor
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * Resend constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Model\Processor $processor
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Model\Processor $processor,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
    ) {
        parent::__construct($context);

        $this->kkmHelper = $kkmHelper;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->processor = $processor;
        $this->errorHelper = $errorHelper;
    }

    /**
     * Execute
     */
    public function execute()
    {
        try {
            $this->validateRequest();

            $entityType = strtolower($this->_request->getParam('entity'));
            $id = $this->getRequest()->getParam('id');

            switch ($entityType) {
                case 'invoice':
                    $entity = $this->invoiceRepository->get($id);
                    $this->processor->proceedSell($entity);
                    $comment = 'Cheque was sent to KKM.';
                    break;
                case 'creditmemo':
                    $entity = $this->creditmemoRepository->get($id);
                    $this->processor->proceedRefund($entity);
                    $comment = 'Refund was sent to KKM.';
                    break;
            }

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
