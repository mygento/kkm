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
use Mygento\Base\Api\Data\RecalculateResultItemInterface;
use Mygento\Base\Helper\Discount;
use Mygento\Base\Model\Payment\Transaction;
use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Api\Data\PaymentInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
use Mygento\Kkm\Helper\Error;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\Atol
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vendor implements \Mygento\Kkm\Model\VendorInterface
{
    const CLIENT_NAME = 'client_name';
    const CLIENT_INN = 'client_inn';

    const TAX_SUM = 'tax_sum';
    const CUSTOM_DECLARATION = 'custom_declaration';
    const COUNTRY_CODE = 'country_code';

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Base\Helper\Discount
     */
    private $kkmDiscount;

    /**
     * @var \Mygento\Kkm\Model\Atol\RequestFactory
     */
    private $requestFactory;

    /**
     * @var \Mygento\Kkm\Model\Atol\ItemFactory
     */
    private $itemFactory;

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
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * To get Frontend URL in backend scope
     * @var \Magento\Framework\Url
     */
    private $urlHelper;

    /**
     * @var \Mygento\Kkm\Model\GetRecalculated
     */
    private $getRecalculated;

    /**
     * @var \Mygento\Kkm\Helper\OrderComment
     */
    private $orderCommentHelper;

    /**
     * @param \Mygento\Base\Helper\Discount $kkmDiscount
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Model\Atol\RequestFactory $requestFactory
     * @param \Mygento\Kkm\Model\Atol\ItemFactory $itemFactory
     * @param \Mygento\Kkm\Model\Atol\PaymentFactory $paymentFactory
     * @param \Mygento\Kkm\Model\Atol\Client $apiClient
     * @param \Magento\Framework\Url $urlHelper
     * @param \Mygento\Kkm\Model\GetRecalculated $getRecalculated
     * @param \Mygento\Kkm\Helper\OrderComment $orderCommentHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Mygento\Base\Helper\Discount $kkmDiscount,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\Atol\RequestFactory $requestFactory,
        \Mygento\Kkm\Model\Atol\ItemFactory $itemFactory,
        \Mygento\Kkm\Model\Atol\PaymentFactory $paymentFactory,
        \Mygento\Kkm\Model\Atol\Client $apiClient,
        \Magento\Framework\Url $urlHelper,
        \Mygento\Kkm\Model\GetRecalculated $getRecalculated,
        \Mygento\Kkm\Helper\OrderComment $orderCommentHelper
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->kkmDiscount = $kkmDiscount;
        $this->requestFactory = $requestFactory;
        $this->itemFactory = $itemFactory;
        $this->apiClient = $apiClient;
        $this->transactionHelper = $transactionHelper;
        $this->requestHelper = $requestHelper;
        $this->attemptHelper = $attemptHelper;
        $this->paymentFactory = $paymentFactory;
        $this->urlHelper = $urlHelper;
        $this->getRecalculated = $getRecalculated;
        $this->orderCommentHelper = $orderCommentHelper;
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

        $this->addCommentToOrder($entity, $response, $txn->getId(), $operation);

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

        $this->addCommentToOrder($entity, $response, $txn->getId(), $operation);

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        $request = $this->buildRequest($invoice);

        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);
        $lastRefundTransaction = $this->transactionHelper->getLastResellRefundTransaction($invoice);

        $externalId = $this->transactionHelper->getExternalId($doneTransaction)
            ?? $this->requestHelper->generateExternalId($invoice);
        $externalId .= '_refund';

        $externalId = $this->transactionHelper->getExternalId($lastRefundTransaction) ?? $externalId;

        //Accordingly to letter from ФНС от 06.08.2018 № ЕД-4-20/15240
        //set ФПД for resell requests.
        $request->setAdditionalCheckProps($this->transactionHelper->getFpd($doneTransaction));
        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_REFUND_OPERATION_TYPE);

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        $request = $this->buildRequest($invoice);

        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        $lastResellTransaction = $this->transactionHelper->getLastResellSellTransaction($invoice);

        $externalId = $this->transactionHelper->getExternalId($doneTransaction)
            ?? $this->requestHelper->generateExternalId($invoice);
        $externalId .= '_resell';

        $externalId = $this->transactionHelper->getExternalId($lastResellTransaction) ?? $externalId;

        //Accordingly to letter from ФНС от 06.08.2018 № ЕД-4-20/15240
        //set ФПД for resell requests.
        $request->setAdditionalCheckProps($this->transactionHelper->getFpd($doneTransaction));
        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_SELL_OPERATION_TYPE);

        return $request;
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
        $order = $salesEntity->getOrder() ?? $salesEntity;
        $storeId = $order->getStoreId();

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create($storeId);
        switch ($salesEntity->getEntityType()) {
            case 'invoice':
                $request->setOperationType(RequestInterface::SELL_OPERATION_TYPE);
                break;
            case 'creditmemo':
                $request->setOperationType(RequestInterface::REFUND_OPERATION_TYPE);
                break;
        }

        $recalculatedReceiptData = $this->getRecalculated->execute($salesEntity);

        $items = [];
        foreach ($recalculatedReceiptData[Discount::ITEMS] as $key => $itemData) {
            //For orders without Shipping (Virtual products)
            if ($key == Discount::SHIPPING && $itemData[Discount::NAME] === null) {
                continue;
            }

            $this->validateItem($itemData);

            //How to handle GiftCards - see Atol API documentation
            $itemPaymentMethod = $this->requestHelper->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_METHOD_ADVANCE
                : ($paymentMethod ?: Item::PAYMENT_METHOD_FULL_PAYMENT);
            $itemPaymentObject = $this->requestHelper->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_OBJECT_PAYMENT
                : ($key == Discount::SHIPPING && $shippingPaymentObject
                    ? $shippingPaymentObject
                    : Item::PAYMENT_OBJECT_BASIC);

            $items[] = $this->buildItem($itemData, $itemPaymentMethod, $itemPaymentObject, $storeId);
        }

        $telephone = $order->getBillingAddress()
            ? (string) $order->getBillingAddress()->getTelephone()
            : '';

        $request
            ->setEntityStoreId($storeId)
            ->setExternalId($this->requestHelper->generateExternalId($salesEntity))
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setEmail($order->getCustomerEmail())
            ->setClientName($clientName)
            ->setClientInn($clientInn)
            ->setPhone($telephone)
            ->setCompanyEmail($this->kkmHelper->getStoreEmail($storeId))
            ->setPaymentAddress($this->kkmHelper->getConfig('atol/payment_address', $storeId))
            ->setSno($this->kkmHelper->getConfig('atol/sno', $storeId))
            ->setInn($this->kkmHelper->getConfig('atol/inn', $storeId))
            ->setCallbackUrl($this->getCallbackUrl($storeId))
            ->setItems($items);

        //Basic payment
        if ($salesEntity->getGrandTotal() > 0.00) {
            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_BASIC)
                        ->setSum(round($salesEntity->getGrandTotal(), 2))
                );
        }

        //"GiftCard applied" payment
        if ($this->requestHelper->isGiftCardApplied($salesEntity)) {
            $giftCardsAmount = $salesEntity->getGiftCardsAmount()
                ?? $salesEntity->getOrder()->getGiftCardsAmount();

            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_AVANS)
                        ->setSum(round($giftCardsAmount, 2))
                );
        }

        //"CustomerBalance applied" payment
        if ($this->requestHelper->isCustomerBalanceApplied($salesEntity)) {
            $customerBalanceAmount = $salesEntity->getCustomerBalanceAmount()
                ?? $salesEntity->getOrder()->getCustomerBalanceAmount();

            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_AVANS)
                        ->setSum(round($customerBalanceAmount, 2))
                );
        }

        return $request;
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getCallbackUrl($storeId = null)
    {
        return $this->kkmHelper->getConfig('atol/callback_url', $storeId)
            ?? $this->urlHelper->setScope($storeId)->getUrl('kkm/frontend/callback', [
                '_secure' => true,
                '_nosid' => true,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null, $operation = '')
    {
        $this->orderCommentHelper->addCommentToOrder($entity, $response, $txnId, $operation);
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

        $trials = $this->attemptHelper->getTrials($entity, UpdateRequestInterface::UPDATE_OPERATION_TYPE);
        $maxUpdateTrials = $this->kkmHelper->getMaxUpdateTrials();

        //Don't send if trials number exceeded
        if ($trials >= $maxUpdateTrials) {
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
            $this->addCommentToOrder($entity, $response, $txn->getTransactionId() ?? null);

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
     * @param RecalculateResultItemInterface $itemData
     * @param string $itemPaymentMethod
     * @param string $itemPaymentObject
     * @param string|null $storeId
     * @return ItemInterface
     */
    private function buildItem($itemData, $itemPaymentMethod, $itemPaymentObject, $storeId = null)
    {
        /** @var ItemInterface $item */
        $item = $this->itemFactory->create();
        $item
            ->setName($itemData[Discount::NAME])
            ->setPrice($itemData[Discount::PRICE])
            ->setSum($itemData[Discount::SUM])
            ->setQuantity($itemData[Discount::QUANTITY] ?? 1)
            ->setTax($itemData[Discount::TAX])
            ->setPaymentMethod($itemPaymentMethod)
            ->setPaymentObject($itemPaymentObject)
            ->setTaxSum($itemData[self::TAX_SUM] ?? 0.0)
            ->setCustomsDeclaration($itemData[self::CUSTOM_DECLARATION] ?? '')
            ->setCountryCode($itemData[self::COUNTRY_CODE] ?? '');
        if ($this->kkmHelper->isMarkingEnabled($storeId) && !empty($itemData[Discount::MARKING])) {
            $item->setMarkingRequired(true);
            $item->setMarking(
                $this->requestHelper->convertMarkingToHex($itemData[Discount::MARKING], $storeId)
            );
        }

        return $item;
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

        try {
            //Make Request to Vendor's API
            $response = $this->apiClient->{$callback}($request);

            //Save transaction data
            $txn = $this->transactionHelper->registerTransaction($entity, $response, $request);
            $this->addCommentToOrder($entity, $response, $txn->getId(), $request->getOperationType());

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
     * @param ResponseInterface $response
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     */
    private function validateResponse($response)
    {
        if ($response->isFailed()) {
            throw new CreateDocumentFailedException(
                __('Response is failed or invalid.'),
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
        //Ошибки при работе с ККТ (cash machine errors)
        if ($response->getErrorCode() < 0) {
            //increment EID and send it
            throw new VendorNonFatalErrorException();
        }

        switch ($response->getErrorCode()) {
            case '1': //Timeout
            case '2': //Incorrect INN (if type = agent) or incorrect Group_Code or Operation
            case '3': //Incorrect Operation
            case '8': //Validation error.
            case '22': //Incorrect group_code
                throw new VendorNonFatalErrorException(
                    __(
                        'Error response from ATOL with code %1. Need to resend with new external_id.',
                        $response->getErrorCode()
                    )
                );

            default:
                throw new CreateDocumentFailedException(
                    __('Error response from ATOL with code %1.', $response->getErrorCode()),
                    $response
                );
        }
    }

    /**
     * @param RecalculateResultItemInterface $item
     * @throws \Exception
     */
    private function validateItem(RecalculateResultItemInterface $item)
    {
        $reason = false;
        if (!isset($item['name']) || $item['name'] === null || $item['name'] === '') {
            $reason = __('One of items has undefined name.');
        }

        if (!isset($item['tax']) || $item['tax'] === null) {
            $reason = __('Item %1 has undefined tax.', $item['name']);
        }

        if ($reason) {
            throw new \Exception(
                __('Can not send data to Atol. Reason: %1', $reason)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function isNeedUpdateStatus()
    {
        return true;
    }
}
