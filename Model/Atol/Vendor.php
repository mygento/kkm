<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Base\Model\Payment\Transaction;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Exception\AuthorizationException;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Model\Source\Atol\ErrorType;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vendor implements \Mygento\Kkm\Model\VendorInterface, \Mygento\Kkm\Model\StatusUpdatable
{
    public const CLIENT_NAME = 'client_name';
    public const CLIENT_INN = 'client_inn';

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\Atol\Client
     */
    private $apiClient;

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /**
     * @var \Mygento\Kkm\Helper\OrderComment
     */
    private $orderCommentHelper;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Model\Atol\Client $apiClient
     * @param \Mygento\Kkm\Helper\OrderComment $orderCommentHelper
     * @param \Mygento\Kkm\Model\Atol\RequestBuilder $requestBuilder
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\Atol\Client $apiClient,
        \Mygento\Kkm\Helper\OrderComment $orderCommentHelper,
        \Mygento\Kkm\Model\Atol\RequestBuilder $requestBuilder
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->apiClient = $apiClient;
        $this->transactionHelper = $transactionHelper;
        $this->requestHelper = $requestHelper;
        $this->attemptHelper = $attemptHelper;
        $this->orderCommentHelper = $orderCommentHelper;
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @inheritdoc
     */
    public function sendSellRequest($request, $invoice = null)
    {
        return $this->sendRequest($request, 'sendSell', $invoice);
    }

    /**
     * @inheritdoc
     */
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

        return $this->sendRequest($request, 'sendRefund', $invoice);
    }

    /**
     * @inheritDoc
     */
    public function sendRefundRequest($request, $creditmemo = null)
    {
        return $this->sendRequest($request, 'sendRefund', $creditmemo);
    }

    /**
     * @inheritdoc
     * @param string $uuid
     * @param bool $useAttempt
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Throwable
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function updateStatus($uuid, $useAttempt = false)
    {
        if ($useAttempt) {
            return $this->tryUpdateStatus($uuid);
        }

        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid, Response::STATUS_WAIT);

        if (!$transaction->getId()) {
            $this->kkmHelper->error("Transaction not found. Uuid: {$uuid}");

            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);

        //TODO: Validate response
        $response = $this->apiClient->receiveStatus($uuid, $entity->getStoreId());

        try {
            $this->validateResponse($response);
        } catch (CreateDocumentFailedException $e) {
            $this->kkmHelper->critical($e);

            if (!$response->getIdForTransaction()) {
                $response->setIdForTransaction($uuid);
            }
        }

        $operation = '';
        switch ($entity->getEntityType()) {
            case 'invoice':
                if ($transaction->getTxnType() === Transaction::TYPE_FISCAL_REFUND) {
                    $txn = $this->transactionHelper->saveResellRefundTransaction($entity, $response);
                    $operation = RequestInterface::RESELL_REFUND_OPERATION_TYPE;
                    break;
                }

                $txn = $this->transactionHelper->saveSellTransaction($entity, $response);
                break;
            case 'creditmemo':
                $txn = $this->transactionHelper->saveRefundTransaction($entity, $response);
                break;
        }

        $this->orderCommentHelper->addCommentToOrder($entity, $response, $txn->getId(), $operation);

        return $response;
    }

    /**
     * Save callback from Atol and return related entity (Invoice or Creditmemo)
     * @param ResponseInterface $response
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return CreditmemoInterface|InvoiceInterface
     */
    public function saveCallback($response)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId(
            $response->getIdForTransaction()
        );
        //TODO: Validate response

        if (!$transaction->getId()) {
            $this->kkmHelper->error("Transaction not found. Uuid: {$response->getIdForTransaction()}");

            throw new \Exception("Transaction not found. Uuid: {$response->getIdForTransaction()}");
        }

        $entity = $this->transactionHelper->getEntityByTransaction($transaction);

        if (!$entity->getId()) {
            throw new NotFoundException(__("Entity for uuid {$response->getIdForTransaction()} not found"));
        }

        $status = $transaction->getKkmStatus();
        if ($status === Response::STATUS_DONE) {
            return $entity;
        }

        $operation = '';
        switch ($entity->getEntityType()) {
            case 'invoice':
                $txn = $this->transactionHelper->saveSellTransaction($entity, $response);
                $operation = $txn->getTxnType() === Transaction::TYPE_FISCAL_REFUND
                    ? RequestInterface::RESELL_REFUND_OPERATION_TYPE
                    : '';
                break;
            case 'creditmemo':
                $txn = $this->transactionHelper->saveRefundTransaction($entity, $response);
                break;
        }

        $this->orderCommentHelper->addCommentToOrder($entity, $response, $txn->getId(), $operation);

        return $entity;
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
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        return $this->requestBuilder->buildRequestForResellSell($invoice);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildRequest(
        $salesEntity,
        $paymentMethod = null,
        $shippingPaymentObject = null,
        array $receiptData = [],
        $clientName = '',
        $clientInn = ''
    ): RequestInterface {
        return $this->requestBuilder->buildRequest(
            $salesEntity,
            $paymentMethod,
            $shippingPaymentObject,
            $receiptData,
            $clientName,
            $clientInn
        );
    }

    /**
     * @param string $uuid
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Throwable
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    private function tryUpdateStatus($uuid)
    {
        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid, Response::STATUS_WAIT);
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$uuid}");
        }

        //Reset flag in order to add one more comment. For case when consumer works as daemon.
        $entity->getOrder()->setData(OrderComment::COMMENT_ADDED_TO_ORDER_FLAG, false);

        $trials = $this->attemptHelper->getTrials($entity, UpdateRequestInterface::UPDATE_OPERATION_TYPE);
        $maxUpdateTrials = $this->kkmHelper->getMaxUpdateTrials($entity->getStoreId());

        //Don't send if trials number exceeded
        if ($trials >= $maxUpdateTrials && !$this->kkmHelper->isRetrySendingEndlessly($entity->getStoreId())) {
            $this->transactionHelper->setKkmStatus($transaction, ResponseInterface::STATUS_FAIL);
            $this->kkmHelper->debug('Request is skipped. Max num of trials exceeded while update');

            throw new \Exception(__('Request is skipped. Max num of trials exceeded while update'));
        }

        //Register sending Attempt
        /** @var TransactionAttemptInterface $attempt */
        $attempt = $this->attemptHelper->registerUpdateAttempt($entity, $transaction);

        try {
            //Make Request to Vendor's API
            $response = $this->apiClient->receiveStatus($uuid, $entity->getStoreId());

            //Save transaction data
            $txn = $this->transactionHelper->registerTransaction($entity, $response);
            $this->orderCommentHelper->addCommentToOrder($entity, $response, $txn->getTransactionId() ?? null);

            //Check response.
            $this->validateResponse($response);

            //Mark attempt as Sent
            $this->attemptHelper->finishAttempt($attempt);
        } catch (\Throwable $e) {
            //Mark attempt as Error
            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        }

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @param callable $callback
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws VendorNonFatalErrorException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Throwable
     * @throws CreateDocumentFailedException
     * @return ResponseInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function sendRequest($request, $callback, $entity = null): ResponseInterface
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

        //Register sending Attempt
        $attempt = $this->attemptHelper->registerAttempt($request, $entity);
        $attempt
            ->setErrorCode(null)
            ->setErrorType(null);
        $response = null;

        try {
            //Make Request to Vendor's API
            /** @var \Mygento\Kkm\Api\Data\ResponseInterface $response */
            $response = $this->apiClient->{$callback}($request);

            //Save transaction data
            $txn = $this->transactionHelper->registerTransaction($entity, $response, $request);
            $this->orderCommentHelper->addCommentToOrder($entity, $response, $txn->getId(), $request->getOperationType());

            //Check response.
            $this->validateResponse($response);

            //Mark attempt as Sent
            $this->attemptHelper->finishAttempt($attempt);
        } catch (AuthorizationException $e) {
            if ($e->getErrorCode() && $e->getErrorType()) {
                $attempt->setErrorCode($e->getErrorCode());
                $attempt->setErrorType($e->getErrorType());
            }

            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        } catch (VendorBadServerAnswerException $e) {
            $attempt->setErrorType(ErrorType::BAD_SERVER_ANSWER);
            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        } catch (CreateDocumentFailedException | VendorNonFatalErrorException $e) {
            $attempt->setErrorType(ErrorType::UNKNOWN);
            $response = $e->getResponse();

            if ($response && $response->getErrorCode() && $response->getErrorType()) {
                $attempt
                    ->setErrorCode($response->getErrorCode())
                    ->setErrorType($response->getErrorType());
            }

            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        } catch (\Throwable $e) {
            //Mark attempt as Error
            $attempt->setErrorType(ErrorType::UNKNOWN);
            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     */
    private function validateResponse($response)
    {
        if ($response->isFailed() || !$response->getUuid()) {
            throw new CreateDocumentFailedException(
                __('Response is failed or invalid. Message: %1', $response->getMessage()),
                $response
            );
        }

        if (!$response instanceof \Mygento\Kkm\Model\Atol\Response) {
            return;
        }

        if (!$response->getErrorCode()) {
            return;
        }

        $this->validateErrorCode($response);
    }

    /**
     * @param \Mygento\Kkm\Model\Atol\Response $response
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     */
    private function validateErrorCode($response)
    {
        $isNonFatalError = $this->kkmHelper->isAtolNonFatalError(
            $response->getErrorCode(),
            $response->getErrorType()
        );

        if ($isNonFatalError) {
            throw new VendorNonFatalErrorException(
                __(
                    'Error response from ATOL with code %1. Need to resend with new external_id.',
                    $response->getErrorCode()
                ),
                $response
            );
        }

        throw new CreateDocumentFailedException(
            __('Error response from ATOL with code %1.', $response->getErrorCode()),
            $response
        );
    }
}
