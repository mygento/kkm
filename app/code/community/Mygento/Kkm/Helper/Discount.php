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
    const VERSION = '1.0.3';

    protected $_code = 'kkm';

    /** Returns item's data as array with properly calculated discount
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param $itemSku
     * @param string $taxValue
     * @param string $taxAttributeCode
     * @param string $shippingTaxValue
     * @return array|mixed
     */
    public function getItemWithDiscount($entity, $itemSku, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '')
    {
        $items = $this->getRecalculated($entity, $taxValue, $taxAttributeCode, $shippingTaxValue)['items'];

        return isset($items[$itemSku]) ? $items[$itemSku] : [];
    }

    /** Returns all items of the entity (order|invoice|creditmemo) with properly calculated discount and properly calculated Sum
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param string $taxValue
     * @param string $taxAttributeCode Set it if info about tax is stored in product in certain attr
     * @param string $shippingTaxValue
     * @return array with calculated items and sum
     */
    public function getRecalculated($entity, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '')
    {
        $generalHelper = Mage::helper($this->_code);
        $generalHelper->addLog("== START == Recalculation of entity prices. Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        $subTotal       = $entity->getData('subtotal');
        $shippingAmount = $entity->getData('shipping_amount');
        $grandTotal     = $entity->getData('grand_total');
        $grandDiscount  = $grandTotal-$subTotal-$shippingAmount;

        $percentageSum = 0;

        $items      = $entity->getAllVisibleItems() ? $entity->getAllVisibleItems() : $entity->getAllItems();
        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $taxValue = $taxAttributeCode ? $this->addTaxValue($taxAttributeCode, $entity, $item) : $taxValue;

            $price    = $item->getData('price');
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal = $item->getData('row_total');

            //Calculate Percentage. The heart of logic.
            $rowPercentage =  $rowTotal / $subTotal;
            $percentageSum += $rowPercentage;

            $discountPerUnit = $rowPercentage * $grandDiscount / $qty;
            $priceWithDiscount = $this->slyFloor($price + $discountPerUnit);

            $entityItem = $this->_buildItem($item, $priceWithDiscount, $taxValue);

            $itemsFinal[$item->getId()] = $entityItem;
            $itemsSum += $entityItem['sum'];
        }

        $generalHelper->addLog("Sum of all percentages: {$percentageSum}");

        //Calculate DIFF!
        $itemsSumDiff = round($this->slyFloor($grandTotal - $itemsSum - $shippingAmount, 3), 2);

        $generalHelper->addLog("Items sum: {$itemsSum}. All Discounts: {$grandDiscount} Diff value: {$itemsSumDiff}");
        if (bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //if: $itemsSumDiff < 0
            $generalHelper->addLog("Notice: Sum of all items is greater than sumWithAllDiscount of entity. ItemsSumDiff: {$itemsSumDiff}");
            $itemsSumDiff = 0.0;
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingItem = [
            'name'      => $this->getShippingName($entity),
            'price'     => $entity->getShippingAmount() + $itemsSumDiff,
            'quantity'  => 1.0,
            'sum'       => $entity->getShippingAmount() + $itemsSumDiff,
            'tax'       => $shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)) {
            $generalHelper->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }
        $generalHelper->addLog("Final result of recalculation:");
        $generalHelper->addLog($receipt);
        $generalHelper->addLog("== STOP == Recalculation of entity prices. ");

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
        $generalHelper->addLog("Item id: {$item->getId()}. Orig price: {$price} Item rowTotal: {$item->getRowTotal()} Price of 1 piece: {$price}. Result of calc:");
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
        return $item->getRowTotal() && $item->getRowTotal() !== '0.0000';
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
}
