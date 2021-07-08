<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Processor;

use Magento\Framework\Exception\InputException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Mygento\Base\Model\Payment\Transaction as TransactionBase;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Helper\TransactionAttempt as TransactionAttemptHelper;
use Mygento\Kkm\Model\Atol\Response;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Send implements SendInterface
{
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var TransactionAttemptHelper
     */
    private $attemptHelper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * Processor constructor.
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param TransactionAttemptHelper $attemptHelper
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param RequestHelper $requestHelper
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        Data $helper,
        TransactionAttemptHelper $attemptHelper,
        TransactionHelper $transactionHelper,
        RequestHelper $requestHelper,
        TransactionAttemptRepositoryInterface $attemptRepository,
        PublisherInterface $publisher
    ) {
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->attemptHelper = $attemptHelper;
        $this->requestHelper = $requestHelper;
        $this->transactionHelper = $transactionHelper;
        $this->attemptRepository = $attemptRepository;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedSell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $vendor = $this->helper->getCurrentVendor($invoice->getStoreId());
        $request = $vendor->buildRequest($invoice);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled($invoice->getStoreId())) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $vendor->sendSellRequest($request);

            return true;
        }

        $queueMessage = $this->requestHelper->getQueueMessage($request);
        $this->helper->debug('Publish request message: ', $queueMessage->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_SELL, $queueMessage);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedRefund($creditmemo, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $vendor = $this->helper->getCurrentVendor($creditmemo->getStoreId());
        $request = $vendor->buildRequest($creditmemo);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled($creditmemo->getStoreId())) {
            $this->helper->debug('Sending request without Queue:', $request->__toArray());
            $vendor->sendRefundRequest($request);

            return true;
        }

        $queueMessage = $this->requestHelper->getQueueMessage($request);
        $this->helper->debug('Publish request message to queue:', $queueMessage->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_REFUND, $queueMessage);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return bool
     */
    private function proceedResellRefund($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $vendor = $this->helper->getCurrentVendor($invoice->getStoreId());
        $request = $vendor->buildRequestForResellRefund($invoice);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        $this->attemptHelper->resetNumberOfTrials($request, $invoice);

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled($invoice->getStoreId())) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $vendor->sendResellRequest($request, $invoice);

            return true;
        }

        $queueMessage = $this->requestHelper->getQueueMessage($request);
        $this->helper->debug('Publish request message: ', $queueMessage->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_RESELL, $queueMessage);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return bool
     */
    public function proceedResellSell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $vendor = $this->helper->getCurrentVendor($invoice->getStoreId());
        $request = $vendor->buildRequestForResellSell($invoice);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        //Reset flag in order to add one more comment.
        $order = $invoice->getOrder();
        $order->setData(OrderComment::COMMENT_ADDED_TO_ORDER_FLAG, false);

        $this->attemptHelper->resetNumberOfTrials($request, $invoice);

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled($invoice->getStoreId())) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $vendor->sendSellRequest($request, $invoice);

            return true;
        }

        $queueMessage = $this->requestHelper->getQueueMessage($request);
        $this->helper->debug('Publish request: ', $queueMessage->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_SELL, $queueMessage);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return bool
     */
    public function proceedFailedResell($invoice, $sync = false, $ignoreTrials = false)
    {
        /** @var \Mygento\Base\Model\Payment\Transaction $lastRefundTxn */
        $lastRefundTxn = $this->transactionHelper->getLastResellRefundTransaction($invoice);

        if ($lastRefundTxn->getKkmStatus() === Response::STATUS_FAIL) {
            return $this->proceedResell($invoice, $sync, false, true);
        }

        //Это может означать, что эта отправка еще висит в очереди.
        //Поэтому надо подгрузить Attempt - и если он с ошибкой, то отправить снова
        if (!$lastRefundTxn->hasChildTransaction()) {
            $attempt = $this->attemptRepository->getByEntityId(
                RequestInterface::RESELL_SELL_OPERATION_TYPE,
                $invoice->getEntityId()
            );

            if ((int) $attempt->getStatus() === TransactionAttemptInterface::STATUS_ERROR) {
                return $this->proceedResellSell($invoice, $sync, false, true);
            }
            if (!$this->helper->isMessageQueueEnabled($invoice->getStoreId())) {
                throw new InputException(__('Can not proceed resell process.'));
            }

            return false;
        }

        //getChildTransactions() with $type argument contains bugs.
        $children = $lastRefundTxn->getChildTransactions();
        foreach ($children as $transaction) {
            if ($transaction->getTxnType() !== TransactionBase::TYPE_FISCAL) {
                continue;
            }

            $isDone = $transaction->getKkmStatus() === Response::STATUS_DONE;
            $isWait = $transaction->getKkmStatus() === Response::STATUS_WAIT;

            if ($isDone || $isWait) {
                return false;
            }
        }

        return $this->proceedResellSell($invoice, $sync, false, true);
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return bool
     */
    public function proceedResell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $storeId = $invoice->getStoreId();
        $this->proceedResellRefund($invoice, $sync, $ignoreTrials, $incrExtId);

        if (!$this->helper->isVendorNeedUpdateStatus($storeId)
            && ($sync || !$this->helper->isMessageQueueEnabled($storeId))
        ) {
            $this->proceedResellSell($invoice, $sync, $ignoreTrials, $incrExtId);
        }

        return true;
    }
}
