<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Processor;

interface SendInterface
{
    public const TOPIC_NAME_REFUND = 'mygento.kkm.message.refund';
    public const TOPIC_NAME_RESELL = 'mygento.kkm.message.resell';
    public const TOPIC_NAME_SELL = 'mygento.kkm.message.sell';

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
    public function proceedSell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false);

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
    public function proceedRefund($creditmemo, $sync = false, $ignoreTrials = false, $incrExtId = false);

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @return bool
     */
    public function proceedResell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false);

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @param bool $incrExtId
     * @return bool
     */
    public function proceedResellSell($invoice, $sync = false, $ignoreTrials = false, $incrExtId = false);

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @return bool
     */
    public function proceedFailedResell($invoice, $sync = false, $ignoreTrials = false);
}
