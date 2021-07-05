<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Base\Api\Data\RecalculateResultItemInterface;
use Mygento\Base\Helper\Discount;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Model\GetRecalculated;

/**
 * Class RequestBuilder
 * @package Mygento\Kkm\Model\CheckOnline
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestBuilder
{
    /**
     * @var Data
     */
    protected $kkmHelper;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var GetRecalculated
     */
    private $getRecalculated;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    public function __construct(
        Data $kkmHelper,
        RequestFactory $requestFactory,
        GetRecalculated $getRecalculated,
        ItemFactory $itemFactory,
        RequestHelper $requestHelper,
        TransactionHelper $transactionHelper
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->requestFactory = $requestFactory;
        $this->getRecalculated = $getRecalculated;
        $this->itemFactory = $itemFactory;
        $this->requestHelper = $requestHelper;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @return RequestInterface
     */
    public function buildRequest($salesEntity): RequestInterface
    {
        /** @var \Mygento\Kkm\Model\CheckOnline\Request $request */
        $request = $this->requestFactory->create();

        switch ($salesEntity->getEntityType()) {
            case 'invoice':
                $request->setOperationType(Request::SELL_OPERATION_TYPE);
                $request->setEntityType('Invoice');
                break;
            case 'creditmemo':
                $request->setOperationType(Request::REFUND_OPERATION_TYPE);
                $request->setEntityType('Refund');
                break;
        }

        $order = $salesEntity->getOrder() ?? $salesEntity;
        $order->setData(OrderComment::COMMENT_ADDED_TO_ORDER_FLAG, false);
        $storeId = $order->getStoreId();
        $telephone = $order->getBillingAddress()
            ? (string) $order->getBillingAddress()->getTelephone()
            : '';

        $request
            ->setEntityStoreId($storeId)
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setClientId($this->kkmHelper->getConfig('checkonline/client_id', $storeId))
            ->setGroup($this->kkmHelper->getConfig('checkonline/group', $storeId))
            ->setExternalId($this->requestHelper->generateExternalId($salesEntity))
            ->setNonCash([(int) round($order->getGrandTotal() * 100, 0)])
            ->setSno((int) $this->kkmHelper->getConfig('checkonline/sno', $storeId))
            ->setPhone($telephone)
            ->setEmail($order->getCustomerEmail())
            ->setPlace($order->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK, true))
            ->setItems($this->buildItems($salesEntity, $storeId))
            ->setFullResponse((bool) $this->kkmHelper->getConfig('checkonline/full_response', $storeId));

        $advancePayment = 0;
        //"GiftCard applied" payment
        if ($this->requestHelper->isGiftCardApplied($salesEntity)) {
            $giftCardsAmount = $salesEntity->getGiftCardsAmount()
                ?? $salesEntity->getOrder()->getGiftCardsAmount();

            $advancePayment += (int) round($giftCardsAmount * 100, 0);
        }

        //"CustomerBalance applied" payment
        if ($this->requestHelper->isCustomerBalanceApplied($salesEntity)) {
            $customerBalanceAmount = $salesEntity->getCustomerBalanceAmount()
                ?? $salesEntity->getOrder()->getCustomerBalanceAmount();

            $advancePayment += (int) round($customerBalanceAmount * 100, 0);
        }

        $request->setAdvancePayment($advancePayment);

        return $request;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
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

        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_REFUND_OPERATION_TYPE);

        return $request;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
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

        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_SELL_OPERATION_TYPE);

        return $request;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string|null $storeId
     * @throws \Exception
     * @return array
     */
    private function buildItems($salesEntity, $storeId = null)
    {
        $items = [];
        $recalculatedItems = $this->getRecalculated->execute($salesEntity);

        foreach ($recalculatedItems[Discount::ITEMS] as $key => $itemData) {
            // For orders without Shipping (Virtual products)
            if ($key == Discount::SHIPPING && $itemData[Discount::NAME] === null) {
                continue;
            }

            $this->validateItem($itemData);

            $itemPaymentMethod = $this->requestHelper->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_METHOD_ADVANCE
                : Item::PAYMENT_METHOD_FULL_PAYMENT;
            $itemPaymentObject = $this->requestHelper->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_OBJECT_PAYMENT
                : Item::PAYMENT_OBJECT_BASIC;

            $itemQty = $itemData[Discount::QUANTITY] ?? 1;

            /** @var Item $item */
            $item = $this->itemFactory->create();
            $item
                ->setName($itemData[Discount::NAME])
                ->setPrice(round($itemData[Discount::PRICE] * 100, 0))
                ->setQuantity($itemQty * 1000)
                ->setTax(Item::TAX_MAPPING[$itemData[Discount::TAX]])
                ->setPaymentMethod($itemPaymentMethod)
                ->setPaymentObject($itemPaymentObject);

            if ($this->kkmHelper->isMarkingEnabled($storeId) && !empty($itemData[Discount::MARKING])) {
                $item->setMarkingRequired(true);
                $item->setMarking(
                    $this->requestHelper->convertMarkingToHexAndEncodeToBase64($itemData[Discount::MARKING], $storeId)
                );
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param RecalculateResultItemInterface $item
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function validateItem($item)
    {
        $reason = '';
        if (!isset($item['name']) || $item['name'] === null || $item['name'] === '') {
            $reason .= __('One of items has undefined name. ');
        }

        if (!isset($item['tax']) || $item['tax'] === null) {
            $reason .= __('Item %1 has undefined tax. ', $item['name']);
        }

        if (!isset($item['price']) || $item['price'] === null) {
            $reason .= __('Item %1 has undefined price. ', $item['name']);
        }

        if (!isset($item['quantity']) || $item['quantity'] === null) {
            $reason .= __('Item %1 has undefined quantity. ', $item['name']);
        }

        if ($reason) {
            throw new \Exception(
                __('Can not send data to Checkonline. Reason: %1', $reason)
            );
        }
    }
}
