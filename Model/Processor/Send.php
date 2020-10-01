<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Processor;

use Magento\Framework\MessageQueue\PublisherInterface;
use Mygento\Base\Model\Payment\Transaction as TransactionBase;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Helper\TransactionAttempt as TransactionAttemptHelper;
use Mygento\Kkm\Model\Atol\Response;
use Mygento\Kkm\Model\VendorInterface;

class Send implements SendInterface
{
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    protected $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

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
     * @param VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param TransactionAttemptHelper $attemptHelper
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param RequestHelper $requestHelper
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        VendorInterface $vendor,
        Data $helper,
        TransactionAttemptHelper $attemptHelper,
        TransactionHelper $transactionHelper,
        RequestHelper $requestHelper,
        TransactionAttemptRepositoryInterface $attemptRepository,
        PublisherInterface $publisher
    ) {
        $this->vendor = $vendor;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedSell($invoice, $sync = false, $ignoreTrials = false)
    {
        $request = $this->vendor->buildRequest($invoice);
        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $this->vendor->sendSellRequest($request);

            return true;
        }

        $this->helper->debug('Publish request: ', $request->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_SELL, $request);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $sync
     * @param bool $ignoreTrials
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedRefund($creditmemo, $sync = false, $ignoreTrials = false)
    {
        $request = $this->vendor->buildRequest($creditmemo);
        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Sending request without Queue:', $request->__toArray());
            $this->vendor->sendRefundRequest($request);

            return true;
        }

        $this->helper->debug('Publish request to queue:', $request->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_REFUND, $request);

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
    public function proceedResellRefund($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false)
    {
        $request = $this->vendor->buildRequestForResellRefund($invoice);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        $this->attemptHelper->resetNumberOfTrials($request, $invoice);

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $this->vendor->sendResellRequest($request, $invoice);

            return true;
        }

        $this->helper->debug('Publish request: ', $request->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_RESELL, $request);

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
        $request = $this->vendor->buildRequestForResellSell($invoice);

        if ($incrExtId) {
            $this->requestHelper->increaseExternalId($request);
        }

        //Reset flag in order to add one more comment.
        $order = $invoice->getOrder();
        $order->setData(VendorInterface::COMMENT_ADDED_TO_ORDER_FLAG, false);

        $this->attemptHelper->resetNumberOfTrials($request, $invoice);

        $request->setIgnoreTrialsNum($ignoreTrials);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Sending request without Queue: ', $request->__toArray());
            $this->vendor->sendSellRequest($request, $invoice);

            return true;
        }

        $this->helper->debug('Publish request: ', $request->__toArray());
        $this->publisher->publish(self::TOPIC_NAME_SELL, $request);

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
            return $this->proceedResellRefund($invoice, false, false, true);
        }

        //Это может означать, что эта отправка еще висит в очереди.
        //Поэтому надо подгрузить Attempt - и если он с ошибкой, то отправить снова
        if (!$lastRefundTxn->hasChildTransaction()) {
            $attempt = $this->attemptRepository->getByEntityId(
                RequestInterface::RESELL_SELL_OPERATION_TYPE,
                $invoice->getEntityId()
            );

            if ($attempt->getStatus() === TransactionAttemptInterface::STATUS_ERROR) {
                return $this->proceedResellSell($invoice, false, false, true);
            }

            return true;
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

        return $this->proceedResellSell($invoice, false, false, true);
    }
}
