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
use Mygento\Kkm\Model\CheckOnline\Client;
use Mygento\Kkm\Helper\TransactionAttempt;
use Mygento\Kkm\Helper\Request as RequestHelper;

class Vendor implements \Mygento\Kkm\Model\VendorInterface
{
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var Client
     */
    private $apiClient;

    /**
     * @var TransactionAttempt
     */
    private $attemptHelper;

    /**
     * @var RequestHelper
     */
    private $requestHelper;


    public function __construct(
        RequestBuilder $requestBuilder,
        Client $apiClient,
        TransactionAttempt $attemptHelper,
        RequestHelper $requestHelper
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->apiClient = $apiClient;
        $this->attemptHelper = $attemptHelper;
        $this->requestHelper = $requestHelper;
    }

    public function sendSellRequest($request, $invoice = null)
    {
        $entity = $entity ?? $this->requestHelper->getEntityByRequest($request);
        $this->attemptHelper->processTrials($request, $entity);

        $attempt = $this->attemptHelper->registerAttempt($request, $entity);

        $this->apiClient->sendSell($request);
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
