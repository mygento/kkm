<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;

interface VendorInterface
{
    const ALREADY_SENT_FLAG = 'kkm_already_sent';
    const SKIP_PAYMENT_METHOD_VALIDATION = 'kkm_skip_payment_method_validation';

    /**
     * Send request to Vendor
     *
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param \Magento\Sales\Api\Data\InvoiceInterface|null $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function sendSellRequest($request, $invoice = null);

    /**
     * Send request to Vendor
     *
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param \Magento\Sales\Api\Data\CreditmemoInterface|null $creditmemo
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function sendRefundRequest($request, $creditmemo = null);

    /**
     * Send resell (refund and again sell) request to Vendor
     *
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param \Magento\Sales\Api\Data\InvoiceInterface|null $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function sendResellRequest(RequestInterface $request, ?InvoiceInterface $invoice = null): ResponseInterface;

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string $paymentMethod
     * @param string $shippingPaymentObject
     * @param array $receiptData
     * @param string $clientName
     * @param string $clientInn
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    public function buildRequest(
        $salesEntity,
        $paymentMethod = null,
        $shippingPaymentObject = null,
        array $receiptData = [],
        $clientName = '',
        $clientInn = ''
    ): RequestInterface;

    /**
     * @param InvoiceInterface $invoice
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    public function buildRequestForResellRefund($invoice): RequestInterface;

    /**
     * @param InvoiceInterface $invoice
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    public function buildRequestForResellSell($invoice): RequestInterface;
}
