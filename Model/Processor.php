<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Mygento\Kkm\Api\ProcessorInterface;

class Processor implements ProcessorInterface
{
    const TOPIC_NAME_SELL = 'mygento.kkm.message.sell';
    const TOPIC_NAME_REFUND = 'mygento.kkm.message.refund';
    const TOPIC_NAME_UPDATE = 'mygento.kkm.message.update';

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
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /**
     * Processor constructor.
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->attemptHelper = $attemptHelper;
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $appendOneTime
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return bool
     */
    public function proceedSell($invoice, $sync = false, $ignoreTrials = false, $appendOneTime = false)
    {
        $request = $this->vendor->buildRequest($invoice);
        $request->setIgnoreTrialsNum($ignoreTrials);

        //Append one trial
        if ($sync && $appendOneTime) {
            $this->attemptHelper->appendOneTrial($request, $invoice);
        }

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
}
