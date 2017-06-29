<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Discount extends Mage_Core_Helper_Abstract
{
    protected $_code = 'kkm';

    const VERSION = '1.0.8';

    protected $generalHelper = null;

    protected $_entity           = null;
    protected $_taxValue         = null;
    protected $_taxAttributeCode = null;
    protected $_shippingTaxValue = null;

    protected $_discountlessSum = 0.00;

    protected $spreadDiscOnAllUnits = null;

    const NAME_UNIT_PRICE      = 'disc_hlpr_price';
    const NAME_SHIPPING_AMOUNT = 'disc_hlpr_shipping_amount';

    /** Returns all items of the entity (order|invoice|creditmemo) with properly calculated discount and properly calculated Sum
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param string $taxValue
     * @param string $taxAttributeCode Set it if info about tax is stored in product in certain attr
     * @param string $shippingTaxValue
     * @return array with calculated items and sum
     */
    public function getRecalculated($entity, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '', $spreadDiscOnAllUnits = false)
    {
        if (!$entity) {
            return;
        }

        $this->_entity              = $entity;
        $this->_taxValue            = $taxValue;
        $this->_taxAttributeCode    = $taxAttributeCode;
        $this->_shippingTaxValue    = $shippingTaxValue;
        $this->generalHelper        = Mage::helper($this->_code);
        $this->spreadDiscOnAllUnits = $spreadDiscOnAllUnits;

        $generalHelper = $this->generalHelper;
        $generalHelper->addLog("== START == Recalculation of entity prices. Helper Version: " . self::VERSION . ".  Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        //If there is no discounts - DO NOTHING
        if ($this->checkSpread()) {
            $this->applyDiscount();
        }

        $this->generalHelper->addLog("== STOP == Recalculation. Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        return $this->buildFinalArray();
    }

    public function applyDiscount()
    {
        $subTotal       = $this->_entity->getData('subtotal_incl_tax');
        $shippingAmount = $this->_entity->getData('shipping_incl_tax');
        $grandTotal     = $this->_entity->getData('grand_total');
        $grandDiscount  = $grandTotal - $subTotal - $shippingAmount;

        $percentageSum = 0;

        $items      = $this->getAllItems();
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $price    = $item->getData('price_incl_tax');
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal = $item->getData('row_total_incl_tax');

            //Calculate Percentage. The heart of logic.
            $denominator   = ($this->spreadDiscOnAllUnits || ($subTotal == $this->_discountlessSum)) ? $subTotal : ($subTotal - $this->_discountlessSum);
            $rowPercentage = $rowTotal / $denominator;

            if (!$this->spreadDiscOnAllUnits && (floatval($item->getDiscountAmount()) === 0.00)) {
                $rowPercentage = 0;
            }
            $percentageSum += $rowPercentage;

            $discountPerUnit   = $rowPercentage * $grandDiscount / $qty;
            $priceWithDiscount = bcadd($price, $discountPerUnit, 2);

            //Set Recalculated unit price for the item
            $item->setData(self::NAME_UNIT_PRICE, $priceWithDiscount);

            $itemsSum += round($priceWithDiscount * $qty, 2);
        }

        $this->generalHelper->addLog("Sum of all percentages: {$percentageSum}");

        //Calculate DIFF!
        $itemsSumDiff = round($this->slyFloor($grandTotal - $itemsSum - $shippingAmount, 3), 2);

        $this->generalHelper->addLog("Items sum: {$itemsSum}. All Discounts: {$grandDiscount} Diff value: {$itemsSumDiff}");
        if (bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //if: $itemsSumDiff < 0
            $this->generalHelper->addLog("Notice: Sum of all items is greater than sumWithAllDiscount of entity. ItemsSumDiff: {$itemsSumDiff}");
            $itemsSumDiff = 0.0;
        }

        //Set Recalculated Shipping Amount
        $this->_entity->setData(self::NAME_SHIPPING_AMOUNT, $this->_entity->getData('shipping_incl_tax') + $itemsSumDiff);
    }

    public function buildFinalArray()
    {
        $grandTotal = $this->_entity->getData('grand_total');

        $items      = $this->getAllItems();
        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $taxValue   = $this->_taxAttributeCode ? $this->addTaxValue($this->_taxAttributeCode, $this->_entity, $item) : $this->_taxValue;
            $price      = $item->getData(self::NAME_UNIT_PRICE) ?: $item->getData('price_incl_tax');
            $entityItem = $this->_buildItem($item, $price, $taxValue);

            $itemsFinal[$item->getId()] = $entityItem;

            $itemsSum += $entityItem['sum'];
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingAmount = $this->_entity->getData(self::NAME_SHIPPING_AMOUNT) ?: $this->_entity->getData('shipping_incl_tax') + 0.00;

        $shippingItem = [
            'name'     => $this->getShippingName($this->_entity),
            'price'    => $shippingAmount,
            'quantity' => 1.0,
            'sum'      => $shippingAmount,
            'tax'      => $this->_shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)) {
            $this->generalHelper->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }

        $this->generalHelper->addLog("Final array:");
        $this->generalHelper->addLog($receipt);

        return $receipt;
    }

    protected function _buildItem($item, $price, $taxValue = '')
    {
        $generalHelper = Mage::helper($this->_code);

        $qty = $item->getQty() ?: $item->getQtyOrdered();
        if (!$qty) {
            throw new Exception('Divide by zero. Qty of the item is equal to zero! Item: ' . $item->getId());
        }

        $entityItem = [
            'price' => round($price, 2),
            'name' => $item->getName(),
            'quantity' => round($qty, 2),
            'sum' => round($price * $qty, 2),
            'tax' => $taxValue,
        ];

        $generalHelper->addLog("Item calculation details:");
        $generalHelper->addLog("Item id: {$item->getId()}. Orig price: {$price} Item rowTotalInclTax: {$item->getData('row_total_incl_tax')} PriceInclTax of 1 piece: {$price}. Result of calc:");
        $generalHelper->addLog($entityItem);

        return $entityItem;
    }

    public function getShippingName($entity)
    {
        return $entity->getShippingDescription()
            ?: ($entity->getOrder() ? $entity->getOrder()->getShippingDescription() : '');
    }

    /**Validation method. It sums up all items and compares it to grandTotal.
     * @param array $receipt
     * @return bool True if all items price equal to grandTotal. False - if not.
     */
    protected function _checkReceipt(array $receipt)
    {
        $sum = array_reduce($receipt['items'], function ($carry, $item) {
            $carry += $item['sum'];
            return $carry;
        });

        return bcsub($sum, $receipt['origGrandTotal'], 2) === '0.00';
    }

    public function isValidItem($item)
    {
        return $item->getData('row_total_incl_tax') !== null;
    }

    public function slyFloor($val, $precision = 2)
    {
        $factor  = 1.00;
        $divider = pow(10, $precision);

        if ($val < 0) {
            $factor = -1.00;
        }

        return (floor(abs($val) * $divider) / $divider) * $factor;
    }

    protected function addTaxValue($taxAttributeCode, $entity, $item)
    {
        if (!$taxAttributeCode) {
            return '';
        }
        $storeId  = $entity->getStoreId();
        $store    = $storeId ? Mage::app()->getStore($storeId) : Mage::app()->getStore();

        $taxValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
            $item->getProductId(),
            $taxAttributeCode,
            $store
        );

        $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $taxAttributeCode);
        if ($attributeModel->getData('frontend_input') == 'select') {
            $taxValue = $attributeModel->getSource()->getOptionText($taxValue);
        }

        return $taxValue;
    }

    /** It checks do we need to spread dicount on all units and sets flag $this->spreadDiscOnAllUnits
     * @return nothing
     */
    public function checkSpread()
    {
        $items = $this->getAllItems();

        $sum                    = 0.00;
        $sumDiscountAmount      = 0.00;
        $discountless           = false;
        $this->_discountlessSum = 0.00;
        foreach ($items as $item) {
            $rowPrice = $item->getData('row_total_incl_tax') - $item->getData('discount_amount');

            if (floatval($item->getData('discount_amount')) === 0.00) {
                $discountless           = true;
                $this->_discountlessSum += $item->getData('row_total_incl_tax');
            }

            $sum               += $rowPrice;
            $sumDiscountAmount += $item->getData('discount_amount');
        }

        $grandTotal     = $this->_entity->getData('grand_total');
        $shippingAmount = $this->_entity->getData('shipping_incl_tax');

        //Есть ли общая скидка на Чек. bccomp returns 0 if operands are equal
        if (bccomp($grandTotal - $shippingAmount - $sum, 0.00, 2) !== 0) {
            $this->generalHelper->addLog("1. Global discount on whole cheque.");

            $this->spreadDiscOnAllUnits = true;
            return true;
        }

        //ок, нет скидки на заказ
        // Есть товар без скидок
        if ($discountless && ($sumDiscountAmount !== 0.00)) {
            $this->generalHelper->addLog("2. Item without discount.");

            return true;
        }

        // Все товары со скидками
        if ($sumDiscountAmount != 0.00) {
            $this->generalHelper->addLog("3. All items with discounts.");

            $this->spreadDiscOnAllUnits = true;
            return true;
        }

        return false;
    }

    public function getDecimalsCountAfterDiv($x, $y)
    {
        $divRes   = strval(round($x / $y, 3));
        $decimals = strrchr($divRes, ".") ? strlen(strrchr($divRes, ".")) - 1 : 0;

        return $decimals;
    }

    public function getAllItems()
    {
        return $this->_entity->getAllVisibleItems() ? $this->_entity->getAllVisibleItems() : $this->_entity->getAllItems();
    }
}
