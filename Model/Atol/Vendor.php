<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;
use Magento\Sales\Model\EntityInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Api\RequestInterface;
use Mygento\Kkm\Api\ResponseInterface;

/**
 * Class Vendor
 * @package Mygento\Kkm\Model\Atol
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vendor implements \Mygento\Kkm\Api\VendorInterface
{
    const COMMENT_ADDED_TO_ORDER_FLAG = 'kkm_comment_added';
    const ALREADY_SENT_FLAG           = 'kkm_already_sent_to_atol';

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

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Base\Helper\Discount $kkmDiscount,
        \Mygento\Kkm\Model\Atol\RequestFactory $requestFactory,
        \Mygento\Kkm\Model\Atol\ItemFactory $itemFactory,
        \Mygento\Kkm\Model\Atol\Client $apiClient,
        \Mygento\Kkm\Helper\Transaction $transactionHelper
    ) {
        $this->kkmHelper         = $kkmHelper;
        $this->kkmDiscount       = $kkmDiscount;
        $this->requestFactory    = $requestFactory;
        $this->itemFactory       = $itemFactory;
        $this->apiClient         = $apiClient;
        $this->transactionHelper = $transactionHelper;
        $this->urlBuilder        = $urlBuilder;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendSell($invoice)
    {
        $request = $this->buildRequest($invoice);

        $response = $this->apiClient->sendSell($request);

        $txn = $this->transactionHelper->saveSellTransaction($invoice, $response);
        $this->addCommentToOrder($invoice, $response, $txn->getId() ?? null);

        $this->validateResponse($response);

        return $response;
    }

    /**
     * @inheritdoc
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @throws \Exception
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendRefund($creditmemo)
    {
        $request = $this->buildRequest($creditmemo);

        $response = $this->apiClient->sendRefund($request);

        $txn = $this->transactionHelper->saveRefundTransaction($creditmemo, $response);
        $this->addCommentToOrder($creditmemo, $response, $txn->getId());

        $this->validateResponse($response);

        return $response;
    }

    /**
     * @inheritdoc
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send($entity)
    {
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Attempt to send empty entity.'));
        }

        $type = $entity->getEntityType();

        switch ($type) {
            case 'invoice':
                $response = $this->sendSell($entity);
                break;
            case 'creditmemo':
                $response = $this->sendRefund($entity);
                break;
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function processQueueMessage(RequestInterface $request) {

    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function updateStatus($uuid)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid);

        if (!$transaction->getId()) {
            $this->kkmHelper->error("Transaction not found. Uuid: {$uuid}");

            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }
        $entity   = $this->transactionHelper->getEntityByTransaction($transaction);

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
    public function buildRequest(EntityInterface $salesEntity): RequestInterface
    {
        $request = $this->requestFactory->create();

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
                    [
                        'type' => Request::PAYMENT_TYPE_BASIC,
                        'sum'  => round($salesEntity->getGrandTotal(), 2),
                    ]
                );
        }

        //"GiftCard applied" payment
        if ($this->isGiftCardApplied($salesEntity)) {
            $request
                ->addPayment(
                    [
                        'type' => Request::PAYMENT_TYPE_AVANS,
                        'sum'  => round($salesEntity->getGiftCardsAmount(), 2),
                    ]
                );
        }

        return $request;
    }

    private function isGiftCard($salesEntity, $itemName)
    {
        $items = $salesEntity->getAllVisibleItems() ?? $salesEntity->getAllItems();

        foreach ($items as $item) {
            $productType = $item->getProductType()
                ?? $this->kkmHelper->getProduct($item->getProductId())->getTypeId();

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
                    'txn_id' => $txnId
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
