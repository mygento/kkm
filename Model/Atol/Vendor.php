<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;
use Magento\Sales\Model\EntityInterface;
use Mygento\Kkm\Api\Data\PaymentInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\Atol
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vendor implements \Mygento\Kkm\Model\VendorInterface
{
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
     * @var \Mygento\Kkm\Helper\Transaction
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
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->kkmHelper         = $kkmHelper;
        $this->kkmDiscount       = $kkmDiscount;
        $this->requestFactory    = $requestFactory;
        $this->itemFactory       = $itemFactory;
        $this->apiClient         = $apiClient;
        $this->transactionHelper = $transactionHelper;
        $this->urlBuilder        = $urlBuilder;
        $this->requestHelper     = $requestHelper;
        $this->attemptHelper     = $attemptHelper;
        $this->productRepository = $productRepository;
        $this->paymentFactory    = $paymentFactory;
    }

    /**
     * @inheritdoc
     */
    public function sendSellRequest($request, $invoice = null)
    {
        return $this->sendRequest($request, [$this->apiClient, 'sendSell'], $invoice);
    }

    /**
     * @inheritDoc
     */
    public function sendRefundRequest($request, $creditmemo = null)
    {
        return $this->sendRequest($request, [$this->apiClient, 'sendRefund'], $creditmemo);
    }

    private function sendRequest($request, $callback, $entity = null)
    {
        $entity = $entity ?? $this->requestHelper->getEntityByRequest($request);

        //Register sending Attempt
        $attempt = $this->attemptHelper->registerAttempt(
            $request,
            $entity->getIncrementId(),
            $entity->getOrderId()
        );

        try {
            //Make Request to Vendor's API
            $response = \call_user_func($callback, $request);

            //Save transaction data
            $txn = $this->transactionHelper->saveSellTransaction($entity, $response);
            $this->addCommentToOrder($entity, $response, $txn->getId() ?? null);

            //Mark attempt as Sent
            $this->attemptHelper->finishAttempt($attempt);
        } catch (\Exception $e) {
            //Mark attempt as Error
            $this->attemptHelper->failAttempt($attempt, $e->getMessage());

            throw $e;
        }

        //Check response. Here attempt is successfully done.
        $this->validateResponse($response);

        return $response;
    }

    /**
     * @inheritdoc
     * @param $uuid
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    public function updateStatus($uuid)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid);

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

    /** Save callback from Atol and return related entity (Invoice or Creditmemo)
     * @param ResponseInterface $response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
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
     */
    public function buildRequest($salesEntity): RequestInterface
    {
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

        $shippingTax   = $this->kkmHelper->getConfig('general/shipping_tax');
        $taxValue      = $this->kkmHelper->getConfig('general/tax_options');
        $attributeCode = '';
        if (!$this->kkmHelper->getConfig('general/tax_all')) {
            $attributeCode = $this->kkmHelper->getConfig('general/product_tax_attr');
        }

        if (!$this->kkmHelper->getConfig('general/default_shipping_name')) {
            $order->setShippingDescription(
                $this->kkmHelper->getConfig('general/custom_shipping_name')
            );
        }

        $recalculatedReceiptData = $this->kkmDiscount->getRecalculated(
            $salesEntity,
            $taxValue,
            $attributeCode,
            $shippingTax
        );

        $items = [];
        foreach ($recalculatedReceiptData['items'] as $key => $itemData) {
            //For orders without Shipping (Virtual products)
            if ($key == 'shipping' && $itemData['name'] === null) {
                continue;
            }

            $this->validateItemArray($itemData);

            //How to handle GiftCards - see Atol API documentation
            $paymentMethod = $this->isGiftCard($salesEntity, $itemData['name'])
                ? Item::PAYMENT_METHOD_ADVANCE
                : Item::PAYMENT_METHOD_FULL_PAYMENT;
            $paymentObject = $this->isGiftCard($salesEntity, $itemData['name'])
                ? Item::PAYMENT_OBJECT_PAYMENT
                : Item::PAYMENT_OBJECT_BASIC;

            $items[] = $this->itemFactory->create()
                ->setName($itemData['name'])
                ->setPrice($itemData['price'])
                ->setSum($itemData['sum'])
                ->setQuantity($itemData['quantity'] ?? 1)
                ->setTax($itemData['tax'])
                ->setPaymentMethod($paymentMethod)
                ->setPaymentObject($paymentObject);
        }

        $telephone = $order->getShippingAddress()
            ? $order->getShippingAddress()->getTelephone()
            : '';

        $request
            ->setExternalId($this->generateExternalId($salesEntity))
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setEmail($order->getCustomerEmail())
            ->setPhone($telephone)
            ->setCompanyEmail($this->kkmHelper->getStoreEmail())
            ->setPaymentAddress($this->kkmHelper->getConfig('atol/payment_address'))
            ->setSno($this->kkmHelper->getConfig('atol/sno'))
            ->setInn($this->kkmHelper->getConfig('atol/inn'))
            ->setCallbackUrl($this->kkmHelper->getCallbackUrl())
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

    private function isGiftCardApplied($entity)
    {
        return $entity->getGiftCardsAmount() + $entity->getCustomerBalanceAmount() > 0.00;
    }

    /**
     * @param \Magento\Sales\Model\EntityInterface $sellEntity Order|Invoice|Creditmemo
     * @param string $postfix
     * @return string
     */
    public function generateExternalId(EntityInterface $entity, $postfix = '')
    {
        $postfix = $postfix ? "_{$postfix}" : '';

        return $entity->getEntityType() . '_' . $entity->getIncrementId() . $postfix;
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

        $order->addStatusHistoryComment($comment);
        $order->setData(self::COMMENT_ADDED_TO_ORDER_FLAG, true);
        $order->save();
    }

    /**
     * @param ResponseInterface $response
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     */
    private function validateResponse($response)
    {
        //TODO: Add more validations
        if ($response->isFailed()) {
            throw new CreateDocumentFailedException(
                __('Reponse is failed or invalid.'),
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
}
