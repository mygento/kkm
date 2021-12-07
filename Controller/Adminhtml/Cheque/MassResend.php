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
use Mygento\Kkm\Api\ResenderInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Model\ResourceModel\ChequeStatus\Grid\CollectionFactory;

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
     * @param Data $configHelper
     * @param ResenderInterface $resender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Data $configHelper,
        ResenderInterface $resender
    ) {
        parent::__construct($context);

        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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
        $entityIdsWithFailedResend = [];
        $entityIdsWithSuccessfulResend = [];

        foreach ($collection as $salesEntity) {
            $errorCode = $salesEntity->getErrorCode();
            $errorType = $salesEntity->getErrorType();
            $needExtIdIncrement = $errorCode && $this->configHelper->isAtolNonFatalError($errorCode, $errorType);

            try {
                $this->resender->resendAsync(
                    $salesEntity->getSalesEntityId(),
                    $salesEntity->getSalesEntityType(),
                    $needExtIdIncrement
                );
            } catch (\Throwable $thr) {
                $entityIdsWithFailedResend[] = $salesEntity->getIncrementId();

                continue;
            }

            $entityIdsWithSuccessfulResend[] = $salesEntity->getIncrementId();
        }

        if (count($entityIdsWithFailedResend)) {
            $this->messageManager->addErrorMessage(
                __(
                    'Failed resend for entities with ids: %1',
                    implode(',', $entityIdsWithFailedResend)
                )
            );
        }

        if (count($entityIdsWithSuccessfulResend)) {
            $this->messageManager->addSuccessMessage(
                __(
                    'Entities have been added to the resending queue : %1',
                    implode(',', $entityIdsWithSuccessfulResend)
                )
            );
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectPath = $this->filter->getComponentRefererUrl() ?: 'kkm/chequestatus/';

        return $resultRedirect->setPath($redirectPath);
    }
}
