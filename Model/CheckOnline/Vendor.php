<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Model\CheckOnline\RequestBuilder;
use Mygento\Kkm\Model\CheckOnline\Client;
use Mygento\Kkm\Helper\TransactionAttempt;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Helper\Data as KkmHelper;
use Magento\Framework\MessageQueue\PublisherInterface;

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

    /**
     * @var OrderComment
     */
    private $orderCommentHelper;

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var KkmHelper
     */
    private $kkmHelper;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    public function __construct(
        RequestBuilder $requestBuilder,
        Client $apiClient,
        TransactionAttempt $attemptHelper,
        RequestHelper $requestHelper,
        OrderComment $orderCommentHelper,
        TransactionHelper $transactionHelper,
        KkmHelper $kkmHelper,
        PublisherInterface $publisher
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->apiClient = $apiClient;
        $this->attemptHelper = $attemptHelper;
        $this->requestHelper = $requestHelper;
        $this->orderCommentHelper = $orderCommentHelper;
        $this->transactionHelper = $transactionHelper;
        $this->kkmHelper = $kkmHelper;
    }

    /**
     * @inheritDoc
     */
    public function sendSellRequest($request, $invoice = null)
    {
        return $this->sendRequest($request, $invoice);
    }

    /**
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    protected function sendRequest($request, $entity = null)
    {
        $entity = $entity ?? $this->requestHelper->getEntityByRequest($request);
        $this->attemptHelper->processTrials($request, $entity);

        $attempt = $this->attemptHelper->registerAttempt($request, $entity);

        try {
            $response = $this->apiClient->sendPostRequest($request);

            $txn = $this->transactionHelper->registerTransaction($entity, $response, $request);
            $this->addCommentToOrder($entity, $response, $txn->getId(), $request->getOperationType());

            $this->validateResponse($response);

            $this->attemptHelper->finishAttempt($attempt);
        } catch (\Throwable $e) {
            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        }

        return $response;
    }

    public function sendRefundRequest($request, $creditmemo = null)
    {
        return $this->sendRequest($request, $creditmemo);
    }

    public function sendResellRequest(RequestInterface $request, ?InvoiceInterface $invoice = null): ResponseInterface
    {
        // TODO: Implement sendResellRequest() method.
    }

    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null, $operation = '')
    {
        $this->orderCommentHelper->addCommentToOrder($entity, $response, $txnId, $operation);
    }

    /**
     * @inheritDoc
     */
    public function updateStatus($uuid, $useAttempt = false)
    {
        // TODO: Implement updateStatus() method.
    }

    /**
     * @inheritDoc
     */
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


    /**
     * @param \Mygento\Kkm\Api\Data\ResponseInterface $response
     */
    private function validateResponse($response)
    {
        if ($response->isFailed()) {
            $errorType = $response->getErrorType() ?? 'Error';
            throw new CreateDocumentFailedException(
                __('%1 response from Checkonline with code %2.', $errorType, $response->getErrorCode()),
                $response
            );
        }

        if ($response->isWait()) {
            $errorType = $response->getErrorType() ?? 'Error';
            throw new VendorNonFatalErrorException(
                __(
                    '%1 response from Checkonline with code %2. Need to resend.',
                    $errorType,
                    $response->getErrorCode()
                )
            );
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    private function scheduleRetry($request)
    {
        if (!$this->kkmHelper->isMessageQueueEnabled($request->getEntityStoreId())) {
            return;
        }


    }
}
