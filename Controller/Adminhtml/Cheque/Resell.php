<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;

class Resell extends \Magento\Backend\App\Action
{
    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_resend';

    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceRepository;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Helper\Error
     */
    private $errorHelper;

    /**
     * @var \Mygento\Kkm\Helper\Resell
     */
    private $resellHelper;

    /**
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Error $errorHelper
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Mygento\Kkm\Helper\Resell $resellHelper
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Error $errorHelper,
        \Mygento\Kkm\Api\Processor\SendInterface $processor,
        \Mygento\Kkm\Helper\Resell $resellHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
    ) {
        parent::__construct($context);

        $this->kkmHelper = $kkmHelper;
        $this->invoiceRepository = $invoiceRepository;
        $this->processor = $processor;
        $this->errorHelper = $errorHelper;
        $this->resellHelper = $resellHelper;
    }

    /**
     * Execute
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        try {
            $this->validateRequest();

            $invoice = $this->invoiceRepository->get($id);

            if ($this->resellHelper->isResellFailed($invoice)) {
                $comment = 'Finishing existing resell process.';

                $isProcessed = $this->processor->proceedFailedResell($invoice, true, true);

                $comment = $isProcessed
                    ? $comment
                    : 'Resell cannot be processed right now. Perhaps previous resell process is opened.';

                $this->getMessageManager()->addSuccessMessage(__($comment));

                return $this->resultRedirectFactory->create()->setUrl(
                    $this->_redirect->getRefererUrl()
                );
            }

            //Кнопка в админке должна отправлять сразу же $sync=true
            $this->processor->proceedResell($invoice, true, true);

            $comment = 'Resell started. Refund was sent to KKM.';

            $this->getMessageManager()->addSuccessMessage(__($comment));
        } catch (NoSuchEntityException $exc) {
            $this->getMessageManager()->addErrorMessage(__('Invoice %1 not found', $id));
            $this->kkmHelper->error("Invoice {$id} not found.");
        } catch (InputException $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
            $this->kkmHelper->error($exc->getMessage());
        } catch (\Exception $exc) {
            $invoice = $this->invoiceRepository->get($id);
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
            $this->kkmHelper->error('Resell failed. Reason: ' . $exc->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($invoice, $exc);
        } catch (\Throwable $thr) {
            $invoice = $this->invoiceRepository->get($id);
            $this->getMessageManager()->addErrorMessage(__('Something went wrong. See log.'));
            $this->kkmHelper->error('Resell failed. Reason: ' . $thr->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($invoice, $thr);
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->_redirect->getRefererUrl()
        );
    }

    /**
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function validateRequest()
    {
        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            $this->kkmHelper->error(
                'Invalid url. No id param:',
                $this->getRequest()->getParams()
            );

            throw new ValidatorException(__('Invalid request. Check logs.'));
        }
    }
}
