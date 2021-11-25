<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Url;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Base\Api\Data\RecalculateResultItemInterface;
use Mygento\Base\Helper\Discount;
use Mygento\Kkm\Api\Data\ItemInterface;
use Mygento\Kkm\Api\Data\PaymentInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\OrderComment;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Model\GetRecalculated;
use Mygento\Kkm\Model\Request\AbstractRequestBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestBuilder extends AbstractRequestBuilder
{
    private const TAX_SUM = 'tax_sum';
    private const CUSTOM_DECLARATION = 'custom_declaration';
    private const COUNTRY_CODE = 'country_code';

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * To get Frontend URL in backend scope
     * @var Url
     */
    private $urlHelper;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Data $kkmHelper,
        GetRecalculated $getRecalculated,
        TransactionHelper $transactionHelper,
        RequestFactory $requestFactory,
        ItemFactory $itemFactory,
        PaymentFactory $paymentFactory,
        Url $urlHelper
    ) {
        parent::__construct(
            $productRepository,
            $kkmHelper,
            $getRecalculated,
            $transactionHelper
        );

        $this->requestFactory = $requestFactory;
        $this->itemFactory = $itemFactory;
        $this->paymentFactory = $paymentFactory;
        $this->urlHelper = $urlHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string $paymentMethod
     * @param string $shippingPaymentObject
     * @param array $receiptData
     * @param string $clientName
     * @param string $clientInn
     * @throws \Exception
     * @return RequestInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
            $itemPaymentMethod = $this->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_METHOD_ADVANCE
                : ($paymentMethod ?: Item::PAYMENT_METHOD_FULL_PAYMENT);
            $itemPaymentObject = $this->isGiftCard($salesEntity, $itemData[Discount::NAME])
                ? Item::PAYMENT_OBJECT_PAYMENT
                : ($key == Discount::SHIPPING && $shippingPaymentObject
                    ? $shippingPaymentObject
                    : Item::PAYMENT_OBJECT_BASIC);

            $items[] = $this->buildItem($itemData, $itemPaymentMethod, $itemPaymentObject, $storeId);
        }

        $telephone = $order->getBillingAddress() ? (string) $order->getBillingAddress()->getTelephone() : '';
        $request
            ->setStoreId($storeId)
            ->setExternalId($this->generateExternalId($salesEntity))
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

        //"GiftCard applied" payment
        if ($this->isGiftCardApplied($salesEntity)) {
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
        if ($this->isCustomerBalanceApplied($salesEntity)) {
            $customerBalanceAmount = $salesEntity->getCustomerBalanceAmount()
                ?? $salesEntity->getOrder()->getCustomerBalanceAmount();

            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_AVANS)
                        ->setSum(round($customerBalanceAmount, 2))
                );
        }

        //Basic payment
        if ($salesEntity->getGrandTotal() > 0.00 || $request->getPayments() === []) {
            $request
                ->addPayment(
                    $this->paymentFactory->create()
                        ->setType(PaymentInterface::PAYMENT_TYPE_BASIC)
                        ->setSum(round($salesEntity->getGrandTotal(), 2))
                );
        }

        return $request;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
     */
    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        $request = parent::buildRequestForResellRefund($invoice);
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        //Accordingly to letter from ФНС от 06.08.2018 № ЕД-4-20/15240
        //set ФПД for resell requests.
        $request->setAdditionalCheckProps($this->transactionHelper->getFpd($doneTransaction));

        return $request;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
     */
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        //Reset flag in order to add one more comment. For case when consumer works as daemon.
        $invoice->getOrder()->setData(OrderComment::COMMENT_ADDED_TO_ORDER_FLAG, false);

        $request = parent::buildRequestForResellSell($invoice);
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        //Accordingly to letter from ФНС от 06.08.2018 № ЕД-4-20/15240
        //set ФПД for resell requests.
        $request->setAdditionalCheckProps($this->transactionHelper->getFpd($doneTransaction));

        return $request;
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
                $this->convertMarkingToHex($itemData[Discount::MARKING], $storeId)
            );
        }

        return $item;
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
     * @param string|null $storeId
     * @return string
     */
    private function getCallbackUrl($storeId = null)
    {
        return $this->kkmHelper->getConfig('atol/callback_url', $storeId)
            ?? $this->urlHelper->setScope($storeId)->getUrl('kkm/frontend/callback', [
                '_secure' => true,
                '_nosid' => true,
            ]);
    }
}
