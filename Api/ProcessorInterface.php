<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api;

interface ProcessorInterface
{
    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @return bool
     */
    public function proceedSell($invoice, $sync = false, $ignoreTrials = false);

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param bool $sync
     * @param bool $ignoreTrials
     * @return bool
     */
    public function proceedRefund($creditmemo, $sync = false, $ignoreTrials = false);

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $sync
     * @param bool $ignoreTrials
     * @return bool
     */
    public function proceedResell($invoice, $sync = false, $ignoreTrials = false);
}
