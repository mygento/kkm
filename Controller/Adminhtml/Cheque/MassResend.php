<?php

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;
use Mygento\Kkm\Model\ResourceModel\TransactionAttempt\CollectionFactory;
use Mygento\Kkm\Helper\TransactionAttempt;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Helper\Error;

class MassResend extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_resend';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var TransactionAttempt
     */
    private $transactionAttemptHelper;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var Data
     */
    private $configHelper;

    /**
     * @var SendInterface
     */
    private $sendProcessor;

    /**
     * @var Error
     */
    private $errorHelper;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param TransactionAttempt $transactionAttemptHelper
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param SendInterface $sendProcessor
     * @param Data $configHelper
     * @param Error $errorHelper
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TransactionAttempt $transactionAttemptHelper,
        CreditmemoRepositoryInterface $creditmemoRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        SendInterface $sendProcessor,
        Data $configHelper,
        Error $errorHelper
    ) {
        parent::__construct($context);

        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->transactionAttemptHelper = $transactionAttemptHelper;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->configHelper = $configHelper;
        $this->sendProcessor = $sendProcessor;
        $this->errorHelper = $errorHelper;
    }

    /**
     * @return Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        /** @var TransactionAttemptInterface $attempt */
        foreach ($collection as $attempt) {
            $isClosed = $this->transactionAttemptHelper->hasSuccessfulAttempt(
                $attempt->getOrderId(),
                $attempt->getOperation(),
                $attempt->getSalesEntityId()
            );

            if ($isClosed) {
                continue;
            }

            try {
                $entityType = $this->transactionAttemptHelper->getEntityType($attempt);
                $incrExtId = $this->configHelper->isAtolNonFatalError($attempt->getErrorCode(), $attempt->getErrorType());
                $salesEntityId = $attempt->getSalesEntityId();

                switch ($entityType) {
                    case 'invoice':
                        $entity = $this->invoiceRepository->get($salesEntityId);
                        $this->sendProcessor->proceedSell($entity, true, true, $incrExtId);
                        $comment = 'Cheque ';
                        break;
                    case 'creditmemo':
                        $entity = $this->creditmemoRepository->get($salesEntityId);
                        $this->sendProcessor->proceedRefund($entity, true, true, $incrExtId);
                        $comment = 'Refund ';
                        break;
                }

                $comment .= $this->configHelper->isMessageQueueEnabled()
                    ? 'was placed to queue for further sending.'
                    : 'was sent to KKM.';

                $this->getMessageManager()->addSuccessMessage(__($comment));
            } catch (NoSuchEntityException $exc) {
                $this->getMessageManager()->addErrorMessage(
                    __(ucfirst($entityType)) . " {$salesEntityId} " . __('not found')
                );
                $this->configHelper->error("Entity {$entityType} with Id {$salesEntityId} not found.");
            } catch (\Exception $exc) {
                $this->getMessageManager()->addErrorMessage($exc->getMessage());
                $this->configHelper->error('Resend failed. Reason: ' . $exc->getMessage());
                $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);
            } catch (\Throwable $thr) {
                $this->getMessageManager()->addErrorMessage(__('Something went wrong. See log.'));
                $this->configHelper->error('Resend failed. Reason: ' . $thr->getMessage());
                $this->errorHelper->processKkmChequeRegistrationError($entity, $thr);
            }
        }

        $this->messageManager->addSuccessMessage(
            __('Cheques for %1 record(s) has been sent.', $collectionSize)
        );

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectPath = $this->filter->getComponentRefererUrl() ?: 'kkm/transactionattempt/';

        return $resultRedirect->setPath($redirectPath);
    }
}