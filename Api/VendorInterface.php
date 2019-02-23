<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Kkm\Api\RequestInterface;
use Mygento\Kkm\Api\ResponseInterface;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\Atol
 */
interface VendorInterface
{
    /**
     * Send invoice to Vendor
     *
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @return \Mygento\Kkm\Api\ResponseInterface
     */
    public function sendSell($invoice);

    /**
     * Send creditmemo to Vendor
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return \Mygento\Kkm\Api\ResponseInterface
     */
    public function sendRefund($creditmemo);

    /**
     * Send cheque (sell or refund) to Vendor
     *
     * @param InvoiceInterface|CreditmemoInterface $entity
     * @return \Mygento\Kkm\Api\ResponseInterface
     */
    public function send($entity);

    /**
     * @param \Mygento\Kkm\Api\RequestInterface $request
     * @return void
     */
    public function processQueueMessage(RequestInterface $request);

    /**
     * @param string $uuid It is Transaction Id on Magento side
     */
    public function updateStatus($uuid);

    /**
     * @param \Magento\Sales\Model\EntityInterface $salesEntity Order|Invoice|Creditmemo
     * @return \Mygento\Kkm\Api\RequestInterface
     */
    public function buildRequest(EntityInterface $salesEntity): RequestInterface;

    /**
     * @param InvoiceInterface|CreditmemoInterface $entity
     * @param \Mygento\Kkm\Api\ResponseInterface $response
     * @param null|mixed $txnId
     */
    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null);
}