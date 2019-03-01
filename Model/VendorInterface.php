<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\Atol
 */
interface VendorInterface
{
    /**
     * Send request to Vendor
     *
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param \Magento\Sales\Api\Data\InvoiceInterface|null $invoice
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function sendSellRequest($request, $invoice = null);

    /**
     * Send request to Vendor
     *
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param \Magento\Sales\Api\Data\CreditmemoInterface|null $creditmemo
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function sendRefundRequest($request, $creditmemo = null);

    /**
     * Send invoice to Vendor
     *
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function sendSell($invoice);

    /**
     * Send creditmemo to Vendor
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function sendRefund($creditmemo);

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return void
     */
    public function processQueueMessage(RequestInterface $request);

    /**
     * @param string $uuid It is Transaction Id on Magento side
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function updateStatus($uuid);

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    public function buildRequest($salesEntity): RequestInterface;

    /**
     * @param InvoiceInterface|CreditmemoInterface $entity
     * @param \Mygento\Kkm\Api\Data\ResponseInterface $response
     * @param null|mixed $txnId
     */
    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null);
}