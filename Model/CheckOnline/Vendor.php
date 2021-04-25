<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Framework\Exception\InputException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
use Mygento\Kkm\Helper\Data as KkmHelper;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Helper\TransactionAttempt;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\CheckOnline
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    public function __construct(
        RequestBuilder $requestBuilder,
        Client $apiClient,
        TransactionAttempt $attemptHelper,
        RequestHelper $requestHelper,
        OrderComment $orderCommentHelper,
        TransactionHelper $transactionHelper,
        KkmHelper $kkmHelper
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

    public function sendRefundRequest($request, $creditmemo = null)
    {
        return $this->sendRequest($request, $creditmemo);
    }

    public function sendResellRequest(RequestInterface $request, ?InvoiceInterface $invoice = null): ResponseInterface
    {
        $invoice = $invoice ?? $this->requestHelper->getEntityByRequest($request);

        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        if (!$doneTransaction->getId()) {
            throw new InputException(
                __(
                    'Invoice %1 does not have transaction with status DONE.',
                    $invoice->getIncrementId()
                )
            );
        }

        //Stop sending if there is 'wait' resell_refund transaction
        if ($this->transactionHelper->isResellOpened($invoice)) {
            throw new InputException(
                __(
                    'Invoice %1 has opened refund transaction.',
                    $invoice->getIncrementId()
                )
            );
        }

        return $this->sendRequest($request, $invoice);
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

    /**
     * @inheritDoc
     */
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        return $this->requestBuilder->buildRequestForResellSell($invoice);
    }

    /**
     * @inheritDoc
     */
    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        return $this->requestBuilder->buildRequestForResellRefund($invoice);
    }

    /**
     * @inheritDoc
     */
    public function isNeedUpdateStatus()
    {
        return false;
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

        $trials = $this->attemptHelper->getTrials($entity, $request->getOperationType());
        $maxTrials = $this->kkmHelper->getMaxTrials($entity->getStoreId());

        //Don't send if trials number exceeded
        if ($trials >= $maxTrials && !$request->isIgnoreTrialsNum()) {
            $this->kkmHelper->debug('Request is skipped. Max num of trials exceeded');
            $this->attemptHelper->resetNumberOfTrials($request, $entity);

            throw new \Exception(__('Request is skipped. Max num of trials exceeded'));
        }

        if ($request->isIgnoreTrialsNum()) {
            $this->attemptHelper->decreaseByOneTrial($request, $entity);
            $request->setIgnoreTrialsNum(false);
        }

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
}
