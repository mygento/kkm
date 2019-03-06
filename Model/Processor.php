<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class Processor
{
    const TOPIC_NAME_SELL   = 'mygento.kkm.message.sell';
    const TOPIC_NAME_REFUND = 'mygento.kkm.message.refund';

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    public function __construct(
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->vendor    = $vendor;
        $this->helper    = $helper;
        $this->publisher = $publisher;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function proceedSell(InvoiceInterface $invoice, $sync = false)
    {
        $request = $this->vendor->buildRequest($invoice);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Queue is disabled. Sending request directly: ', $request->jsonSerialize());
            $this->vendor->sendSellRequest($request);

            return true;
        }

        $this->helper->debug('Publish request: ', $request->jsonSerialize());
        $this->publisher->publish(self::TOPIC_NAME_SELL, $request);

        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $sync
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function proceedRefund(CreditmemoInterface $creditmemo, $sync = false)
    {
        $request = $this->vendor->buildRequest($creditmemo);

        if ($sync || !$this->helper->isMessageQueueEnabled()) {
            $this->helper->debug('Queue is disabled. Sending request directly: ', $request->jsonSerialize());
            $this->vendor->sendRefundRequest($request);

            return true;
        }

        $this->helper->debug('Publish request: ', $request->jsonSerialize());
        $this->publisher->publish(self::TOPIC_NAME_REFUND, $request);

        return true;
    }
}
