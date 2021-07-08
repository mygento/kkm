<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Mygento\Base\Service\RecalculatorFacade;
use Mygento\Kkm\Helper\Data;

class GetRecalculated
{
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $configHelper;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterfaceFactory
     */
    private $orderItemFactory;

    /**
     * @var \Mygento\Base\Service\RecalculatorFacade
     */
    private $recalculatorFacade;

    /**
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemFactory
     * @param \Mygento\Base\Service\RecalculatorFacade $recalculatorFacade
     */
    public function __construct(
        Data $kkmHelper,
        OrderInterfaceFactory $orderFactory,
        OrderItemInterfaceFactory $orderItemFactory,
        RecalculatorFacade $recalculatorFacade
    ) {
        $this->configHelper = $kkmHelper;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->recalculatorFacade = $recalculatorFacade;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @throws \Exception
     * @return array|null
     */
    public function execute($salesEntity)
    {
        $orderMock = $this->getMockOrder($salesEntity);

        //Особенность отправки чеков в ККМ: цены продуктов должны быть оригинальными
        //а GiftCard и StoreCredit должны быть отправлены как аванс
        $giftCardsAmount = $orderMock->getData('gift_cards_amount')
            ?? $salesEntity->getOrder()->getData('gift_cards_amount');
        $customerBalanceAmount = $orderMock->getData('customer_balance_amount')
            ?? $salesEntity->getOrder()->getData('customer_balance_amount');

        $orderMock->setGrandTotal(round(
            $orderMock->getGrandTotal()
            //Magento Commerce Features
            + $giftCardsAmount
            + $customerBalanceAmount,
            4
        ));

        $storeId = $salesEntity->getStoreId();

        $args = $this->collectArguments($storeId);

        if (!$this->configHelper->getConfig('general/default_shipping_name', $storeId)) {
            $shippingDescription = $this->configHelper->getConfig('general/custom_shipping_name', $storeId);
            $orderMock->setShippingDescription($shippingDescription);
        }

        $isSpreadAllowed = (bool) $this->configHelper->getConfig('recalculating/spread_discount', $storeId);
        $isSplitAllowed = (bool) $this->configHelper->getConfig('recalculating/split_allowed', $storeId);

        $isSplit = ($isSplitAllowed & 1) << 1;
        $isSpread = ($isSpreadAllowed & 1) << 2;

        $applyAlgo = $this->configHelper->getConfig('recalculating/apply_algorithm', $storeId);
        if (!$applyAlgo) {
            return $this->recalculatorFacade->executeWithoutCalculation($orderMock, ...$args);
        }

        switch ($isSplit + $isSpread) {
            case 2:  // true false
                return $this->recalculatorFacade->executeWithSplitting($orderMock, ...$args);
            case 4:  // false true
                return $this->recalculatorFacade->executeWithSpreading($orderMock, ...$args);
            case 6:  // true true
                return $this->recalculatorFacade->executeWithSpreadingAndSplitting($orderMock, ...$args);
            default: //false false
                return $this->recalculatorFacade->execute($orderMock, ...$args);
        }
    }

    /**
     * Чтобы не повлиять на данные оригинальной сущности
     * возвращает заказ (OrderInterface),
     * полностью дублирующий сущность (CreditmemoInterface или InvoiceInterface)
     *
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @return OrderInterface
     */
    public function getMockOrder($salesEntity): OrderInterface
    {
        /** @var OrderInterface $orderMock */
        $orderMock = $this->orderFactory->create(['data' => $salesEntity->getData()]);
        $orderMock->setId($salesEntity->getOrder()->getId());
        $orderMock->setEntityId($salesEntity->getOrder()->getId());
        $orderMock->setIncrementId($salesEntity->getOrder()->getIncrementId());

        $orderMock->setShippingDescription($salesEntity->getOrder()->getShippingDescription());

        $newTotal = 0;
        $newItems = [];
        foreach ($salesEntity->getItems() as $item) {
            $orderItem = $salesEntity->getOrder()->getItemById($item->getOrderItemId());
            if ($orderItem->isDummy()) {
                continue;
            }

            $giftCardAmount = $orderItem->getData('gift_cards_amount');
            $customerBalanceAmount = $orderItem->getData('customer_balance_amount');

            $add = static function ($val) use ($giftCardAmount, $customerBalanceAmount) {
                return $val === null
                    ? $val
                    : bcadd($val, bcadd($giftCardAmount, $customerBalanceAmount, 4), 4);
            };

            /** @var \Magento\Sales\Api\Data\OrderItemInterface $itemMock */
            $itemMock = $this->orderItemFactory->create(['data' => $item->getData()]);

            $this->updateMarking($itemMock, $item, $salesEntity->getStoreId());

            $qty = $item->getQty();
            $newPriceAdd = round(($giftCardAmount + $customerBalanceAmount) / $qty, 2);

            //Нужно восстановить первозданные цены и суммы. Без вычета Подарочной карты.
            $itemMock
                ->setPrice($item->getPrice() + $newPriceAdd)
                ->setPriceInclTax($item->getPriceInclTax() + $newPriceAdd)
                ->setBasePrice($item->getBasePrice() + $newPriceAdd)
                ->setBasePriceInclTax($item->getBasePriceInclTax() + $newPriceAdd)
//                    ->setTaxAmount($newItemTax)
//                    ->setBaseTaxAmount($newItemTax)
                ->setRowTotal($add($item->getRowTotal()))
                ->setRowTotalInclTax($add($item->getRowTotalInclTax()))
                ->setBaseRowTotal($add($item->getBaseRowTotal()))
                ->setBaseRowTotalInclTax($add($item->getBaseRowTotalInclTax()))
                ->setQtyOrdered($qty);

            $newTotal += $itemMock->getRowTotalInclTax();

            $newItems[] = $itemMock->setId($item->getId());
        }

        $orderMock
            ->setSubtotal($newTotal)
            ->setBaseSubtotal($newTotal)
            ->setSubtotalInclTax($newTotal)
            ->setBaseSubtotalInclTax($newTotal);

        return $orderMock->setItems($newItems);
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    private function collectArguments(?int $storeId): array
    {
        $shippingTax = $this->configHelper->getConfig('general/shipping_tax', $storeId);
        $taxValue = $this->configHelper->getConfig('general/tax_options', $storeId);
        $attributeCode = '';
        if (!$this->configHelper->getConfig('general/tax_all', $storeId)) {
            $attributeCode = $this->configHelper->getConfig('general/product_tax_attr', $storeId);
        }

        $markingAttribute = '';
        $markingListAttribute = '';
        $markingRefundAttribute = '';

        if ($this->configHelper->isMarkingEnabled($storeId)) {
            $markingAttribute = $this->configHelper->getMarkingShouldField($storeId);
            $markingListAttribute = $this->configHelper->getMarkingField($storeId);
            $markingRefundAttribute = $this->configHelper->getMarkingRefundField($storeId);
        }

        return [
            $taxValue,
            $attributeCode,
            $shippingTax,
            $markingAttribute,
            $markingListAttribute,
            $markingRefundAttribute,
        ];
    }

    /**
     * @param OrderItemInterface $itemMock
     * @param CreditmemoItemInterface|InvoiceItemInterface $item
     * @param mixed $storeId
     */
    private function updateMarking($itemMock, $item, $storeId): void
    {
        $markingAttribute = $this->configHelper->getMarkingShouldField($storeId);
        $markingListAttribute = $this->configHelper->getMarkingField($storeId);
        $markingRefundAttribute = $this->configHelper->getMarkingRefundField($storeId);

        $itemMock->setData($markingAttribute, $item->getOrderItem()->getData($markingAttribute));
        $itemMock->setData($markingListAttribute, $item->getOrderItem()->getData($markingListAttribute));
        $itemMock->setData($markingRefundAttribute, $item->getOrderItem()->getData($markingRefundAttribute));
    }
}
