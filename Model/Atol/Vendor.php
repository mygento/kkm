<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Base\Helper\Discount;
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
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

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
     * Vendor constructor.
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
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     *
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
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->kkmDiscount = $kkmDiscount;
        $this->requestFactory = $requestFactory;
        $this->itemFactory = $itemFactory;
        $this->apiClient = $apiClient;
        $this->transactionHelper = $transactionHelper;
        $this->urlBuilder = $urlBuilder;
        $this->requestHelper = $requestHelper;
        $this->attemptHelper = $attemptHelper;
        $this->productRepository = $productRepository;
        $this->paymentFactory = $paymentFactory;
        $this->urlHelper = $urlHelper;
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
    public function sendResellRequest(RequestInterface $request, $invoice): ResponseInterface
    {
        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        if (!$doneTransaction) {
            throw new LocalizedException(
                __(
                    'Invoice %s does not have transaction with status DONE.',
                    $invoice->getIncrementId()
                )
            );
        }

        $externalId = $this->transactionHelper->getExternalId($doneTransaction)
            ?? $this->generateExternalId($invoice);
        $externalId .= '_refund';

        //Accordingly to letter from ФНС от 06.08.2018 № ЕД-4-20/15240
        //set ФПД for resell requests.
        $request->setAdditionalCheckProps($this->transactionHelper->getFpd($doneTransaction));
        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_REFUND_OPERATION_TYPE);

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
        $response = $this->apiClient->receiveStatus($uuid);

        switch ($entity->getEntityType()) {
            case 'invoice':
                $txn = $this->transactionHelper->saveSellTransaction($entity, $response);
                break;
            case 'creditmemo':
                $txn = $this->transactionHelper->saveRefundTransaction($entity, $response);
                break;
        }

        $this->addCommentToOrder($entity, $response, $txn->getId());

        return $response;
    }

    /**
     * Save callback from Atol and return related entity (Invoice or Creditmemo)
     * @param ResponseInterface $response
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\CreditmemoInterface|\Magento\Sales\Model\Order\Invoice
     */
    public function saveCallback($response)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId(
            $response->getUuid()
        );
        //TODO: Validate response

        if (!$transaction->getId()) {
            $this->kkmHelper->error("Transaction not found. Uuid: {$response->getUuid()}");

            throw new \Exception("Transaction not found. Uuid: {$response->getUuid()}");
        }

        $entity = $this->transactionHelper->getEntityByTransaction($transaction);

        $status = $transaction->getKkmStatus();
        if ($status === Response::STATUS_DONE) {
            return $entity;
        }

        switch ($entity->getEntityType()) {
            case 'invoice':
                $txn = $this->transactionHelper->saveSellTransaction($entity, $response);
                break;
            case 'creditmemo':
                $txn = $this->transactionHelper->saveRefundTransaction($entity, $response);
                break;
        }

        $this->addCommentToOrder($entity, $response, $txn->getId());

        return $entity;
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
        /** @var RequestInterface $request */
        $request = $this->requestFactory->create();
        switch ($salesEntity->getEntityType()) {
            case 'invoice':
                $request->setOperationType(RequestInterface::SELL_OPERATION_TYPE);
                break;
            case 'creditmemo':
                $request->setOperationType(RequestInterface::REFUND_OPERATION_TYPE);
                break;
        }

        $order = $salesEntity->getOrder() ?? $salesEntity;

        $shippingTax = $this->kkmHelper->getConfig('general/shipping_tax');
        $taxValue = $this->kkmHelper->getConfig('general/tax_options');
        $attributeCode = '';
        if (!$this->kkmHelper->getConfig('general/tax_all')) {
            $attributeCode = $this->kkmHelper->getConfig('general/product_tax_attr');
        }

        if (!$this->kkmHelper->getConfig('general/default_shipping_name')) {
            $shippingDescription = $this->kkmHelper->getConfig('general/custom_shipping_name');
            $order->setShippingDescription($shippingDescription);
        }
        $this->configureDiscountHelper();
        $markingAttribute = '';
        $markingListAttribute = '';

        if ($this->kkmHelper->isMarkingEnabled()) {
            $markingAttribute = $this->kkmHelper->getMarkingShouldField();
            $markingListAttribute = $this->kkmHelper->getMarkingField();
        }

        $recalculatedReceiptData = $receiptData
            ?: $this->kkmDiscount->getRecalculated(
                $salesEntity,
                $taxValue,
                $attributeCode,
                $shippingTax,
                $markingAttribute,
                $markingListAttribute
            );

        $items = [];
        foreach ($recalculatedReceiptData[Discount::ITEMS] as $key => $itemData) {
            //For orders without Shipping (Virtual products)
            if ($key == Discount::SHIPPING && $itemData[Discount::NAME] === null) {
                continue;
            }

            $this->validateItemArray($itemData);

            //How to handle GiftCards - see Atol API documentation
            $itemPaymentMethod = $this->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_METHOD_ADVANCE
                : ($paymentMethod ?: Item::PAYMENT_METHOD_FULL_PAYMENT);
            $itemPaymentObject = $this->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_OBJECT_PAYMENT
                : ($key == Discount::SHIPPING && $shippingPaymentObject
                    ? $shippingPaymentObject
                    : Item::PAYMENT_OBJECT_BASIC);

            $items[] = $this->buildItem($itemData, $itemPaymentMethod, $itemPaymentObject);
        }

        $telephone = $order->getBillingAddress()
            ? (string) $order->getBillingAddress()->getTelephone()
            : '';

        $request
            ->setExternalId($this->generateExternalId($salesEntity))
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setEmail($order->getCustomerEmail())
            ->setClientName($clientName)
            ->setClientInn($clientInn)
            ->setPhone($telephone)
            ->setCompanyEmail($this->kkmHelper->getStoreEmail())
            ->setPaymentAddress($this->kkmHelper->getConfig('atol/payment_address'))
            ->setSno($this->kkmHelper->getConfig('atol/sno'))
            ->setInn($this->kkmHelper->getConfig('atol/inn'))
            ->setCallbackUrl($this->getCallbackUrl())
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
        if ($this->isGiftCardApplied($salesEntity)) {
            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_AVANS)
                        ->setSum(round($salesEntity->getGiftCardsAmount(), 2))
                );
        }

        return $request;
    }

    /**
     * @param \Magento\Sales\Model\EntityInterface $entity Order|Invoice|Creditmemo
     * @param string $postfix
     * @return string
     */
    public function generateExternalId(EntityInterface $entity, $postfix = '')
    {
        $postfix = $postfix ? "_{$postfix}" : '';

        return $entity->getEntityType() . '_' . $entity->getStoreId() . '_' . $entity->getIncrementId() . $postfix;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->kkmHelper->getConfig('atol/callback_url')
            ?? $this->urlHelper->getUrl('kkm/frontend/callback', [
                '_secure' => true,
                '_nosid' => true,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null)
    {
        $order = $entity->getOrder();

        if ($order->getData(self::COMMENT_ADDED_TO_ORDER_FLAG)) {
            return;
        }

        $message = ucfirst($entity->getEntityType()) . ': ' . $entity->getIncrementId() . '. ';
        $message .= $response->getMessage();

        if ($txnId) {
            $href =
                $this->urlBuilder->getUrl(
                    'sales/transactions/view',
                    [
                        'txn_id' => $txnId,
                    ]
                );

            $message .= " <a href='{$href}'>Transaction id: {$txnId}</a>";
        }

        $comment = __('[ATOL] Cheque was sent. %1', $message);

        if ($response->getStatus() == Response::STATUS_DONE
            && $order->getStatus() == Error::ORDER_KKM_FAILED_STATUS
            && $this->kkmHelper->getOrderStatusAfterKkmTransactionDone()
        ) {
            $order->addCommentToStatusHistory(
                $comment,
                $this->kkmHelper->getOrderStatusAfterKkmTransactionDone()
            );
        } else {
            $order->addCommentToStatusHistory($comment);
            $order->setData(self::COMMENT_ADDED_TO_ORDER_FLAG, true);
        }

        $order->save();
    }

    /**
     * Set mode flags for Discount logic
     */
    protected function configureDiscountHelper()
    {
        $applyAlgo = $this->kkmHelper->getConfig('recalculating/apply_algorithm');
        $this->kkmDiscount->setDoCalculation((bool) $applyAlgo);
        if ($applyAlgo) {
            $isSpreadAllowed = $this->kkmHelper->getConfig('general/spread_discount');
            $isSplitAllowed = $this->kkmHelper->getConfig('general/split_allowed');

            $this->kkmDiscount->setSpreadDiscOnAllUnits((bool) $isSpreadAllowed);
            $this->kkmDiscount->setIsSplitItemsAllowed((bool) $isSplitAllowed);
        }
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
            $this->kkmHelper->debug('Request is skipped. Max num of trials exceeded');

            throw new \Exception(__('Request is skipped. Max num of trials exceeded'));
        }

        //Register sending Attempt
        /** @var TransactionAttemptInterface $attempt */
        $attempt = $this->attemptHelper->registerUpdateAttempt($entity, $transaction);

        try {
            //Make Request to Vendor's API
            $response = $this->apiClient->receiveStatus($uuid);

            //Save transaction data
            /** @var TransactionInterface $txn */
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
     * @param array $itemData
     * @param string $itemPaymentMethod
     * @param string $itemPaymentObject
     * @return ItemInterface
     */
    private function buildItem(array $itemData, $itemPaymentMethod, $itemPaymentObject)
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
        if ($this->kkmHelper->isMarkingEnabled() && !empty($itemData[Discount::MARKING])) {
            $item->setMarkingRequired(true);
            $item->setMarking(
                $this->convertMarkingToHex($itemData[Discount::MARKING])
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
        $maxTrials = $this->kkmHelper->getMaxTrials();

        //Don't send if trials number exceeded
        if ($trials >= $maxTrials && !$request->isIgnoreTrialsNum()) {
            $this->kkmHelper->debug('Request is skipped. Max num of trials exceeded');
            $this->attemptHelper->resetNumberOfTrials($request, $entity);

            throw new \Exception(__('Request is skipped. Max num of trials exceeded'));
        }

        //Register sending Attempt
        $attempt = $this->attemptHelper->registerAttempt($request, $entity);

        try {
            //Make Request to Vendor's API
            $response = $this->apiClient->{$callback}($request);

            //Save transaction data
            $txn = $this->transactionHelper->registerTransaction($entity, $response, $request);
            $this->addCommentToOrder($entity, $response, $txn->getId() ?? null);

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
     * Check does the item works as gift card. For Magento Commerce only
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @param string $itemName
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    private function isGiftCard($salesEntity, $itemName)
    {
        $items = $salesEntity->getAllVisibleItems() ?? $salesEntity->getAllItems();

        if (!defined('ProductType::TYPE_GIFTCARD')) {
            return false;
        }

        foreach ($items as $item) {
            $productType = $item->getProductType()
                ?? $this->productRepository->getById($item->getProductId())->getTypeId();

            $giftCardType = ProductType::TYPE_GIFTCARD;
            if (strpos($item->getName(), $itemName) !== false && $productType == $giftCardType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    private function isGiftCardApplied($entity)
    {
        return $entity->getGiftCardsAmount() + $entity->getCustomerBalanceAmount() > 0.00;
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
     * @param array $item
     * @throws \Exception
     */
    private function validateItemArray(array $item)
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
     * @param string $marking
     * @return string
     */
    private function convertMarkingToHex(string $marking): string
    {
        $productCode = '444D';
        $gtin = substr($marking, 2, $this->kkmHelper->getConfig('marking/gtin_length'));
        $serialNumber = substr($marking, 2 + $this->kkmHelper->getConfig('marking/gtin_length') + 2);
        $gtinHex = $this->normalizeHex(dechex($gtin));
        $serialHex = $this->normalizeHex(bin2hex($serialNumber));

        return trim(chunk_split(strtoupper($productCode . $gtinHex . $serialHex), 2, ' '));
    }

    /**
     * @param string $hex
     * @return string
     */
    private function normalizeHex(string $hex): string
    {
        if (strlen($hex) % 2 > 0) {
            $hex = '0' . $hex;
        }

        return $hex;
    }
}
