<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\ResenderInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\TransactionAttempt;
use Mygento\Kkm\Model\ResourceModel\TransactionAttempt\CollectionFactory;

class MassResend extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_resend';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var TransactionAttempt
     */
    private $transactionAttemptHelper;

    /**
     * @var Data
     */
    private $configHelper;

    /**
     * @var ResenderInterface
     */
    private $resender;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param TransactionAttempt $transactionAttemptHelper
     * @param Data $configHelper
     * @param ResenderInterface $resender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TransactionAttempt $transactionAttemptHelper,
        Data $configHelper,
        ResenderInterface $resender
    ) {
        parent::__construct($context);

        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->transactionAttemptHelper = $transactionAttemptHelper;
        $this->configHelper = $configHelper;
        $this->resender = $resender;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $attemptIdsWithFailedResend = [];
        $attemptIdsWithSuccessfulResend = [];

        /** @var TransactionAttemptInterface $attempt */
        foreach ($collection as $attempt) {
            $isResendAvailable = $this->transactionAttemptHelper->isResendAvailable($attempt);

            if (!$isResendAvailable) {
                continue;
            }

            $entityType = $this->transactionAttemptHelper->getEntityType($attempt);
            $needExtIdIncrement = $this->configHelper->isAtolNonFatalError(
                $attempt->getErrorCode(),
                $attempt->getErrorType()
            );
            $salesEntityId = $attempt->getSalesEntityId();

            try {
                $this->resender->resend($salesEntityId, $entityType, $needExtIdIncrement);
            } catch (\Throwable $thr) {
                $attemptIdsWithFailedResend[] = $attempt->getId();

                continue;
            }

            $attemptIdsWithSuccessfulResend[] = $attempt->getId();
        }

        if (count($attemptIdsWithFailedResend)) {
            $this->messageManager->addErrorMessage(
                __(
                    'Failed resend for attempts with ids: %1',
                    implode(',', $attemptIdsWithFailedResend)
                )
            );
        }

        if (count($attemptIdsWithSuccessfulResend)) {
            $this->messageManager->addSuccessMessage(
                __(
                    'Successful resend for attempts with ids: %1',
                    implode(',', $attemptIdsWithSuccessfulResend)
                )
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectPath = $this->filter->getComponentRefererUrl() ?: 'kkm/transactionattempt/';

        return $resultRedirect->setPath($redirectPath);
    }
}
