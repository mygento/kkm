<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Mygento\Kkm\Api\ProcessorInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\TransactionAttempt as TransactionAttemptHelper;

class Processor implements ProcessorInterface
{
    public const TOPIC_NAME_SELL = 'mygento.kkm.message.sell';
    public const TOPIC_NAME_REFUND = 'mygento.kkm.message.refund';
    public const TOPIC_NAME_RESELL = 'mygento.kkm.message.resell';
    public const TOPIC_NAME_UPDATE = 'mygento.kkm.message.update';

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
     * Processor constructor.
     * @param VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param TransactionAttemptHelper $attemptHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        VendorInterface $vendor,
        Data $helper,
        TransactionAttemptHelper $attemptHelper,
        PublisherInterface $publisher
    ) {
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->attemptHelper = $attemptHelper;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedResell($invoice, $sync = false, $ignoreTrials = false)
    {
        $request = $this->vendor->buildRequestForResell($invoice);

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
}
