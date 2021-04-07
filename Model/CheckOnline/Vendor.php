<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Model\CheckOnline\RequestBuilder;
use Mygento\Kkm\Model\CheckOnline\ClientFactory;

class Vendor implements \Mygento\Kkm\Model\VendorInterface
{
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(
        RequestBuilder $requestBuilder,
        ClientFactory $clientFactory
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->clientFactory = $clientFactory;
    }

    public function sendSellRequest($request, $invoice = null)
    {
        /** @var \Mygento\Kkm\Model\CheckOnline\Client $client */
        $client = $this->clientFactory->create();
        $client->sendSell($request);
    }

    public function sendRefundRequest($request, $creditmemo = null)
    {
        // TODO: Implement sendRefundRequest() method.
    }

    public function sendResellRequest(RequestInterface $request, ?InvoiceInterface $invoice = null): ResponseInterface
    {
        // TODO: Implement sendResellRequest() method.
    }

    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null, $operation = '')
    {
        // TODO: Implement addCommentToOrder() method.
    }

    public function updateStatus($uuid, $useAttempt = false)
    {
        // TODO: Implement updateStatus() method.
    }

    public function buildRequest(
        $salesEntity,
        $paymentMethod = null,
        $shippingPaymentObject = null,
        array $receiptData = [],
        $clientName = '',
        $clientInn = ''
    ): RequestInterface {
        return $this->requestBuilder->buildRequest($salesEntity);
    }

    public function buildRequestForResellSell($invoice): RequestInterface
    {
        // TODO: Implement buildRequestForResellSell() method.
    }

    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        // TODO: Implement buildRequestForResellRefund() method.
    }
}
