<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Discount extends Mage_Core_Helper_Abstract
{
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

        $sumWithAllDiscount = $grandTotal - $shippingAmount;

        $percentageSum = 0;

        $items      = $entity->getAllVisibleItems() ? $entity->getAllVisibleItems() : $entity->getAllItems();
        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach($items as $item) {
            if (!$item->getRowTotal() || $item->getRowTotal() === '0.0000') {
                continue;
            }

            if ($taxAttributeCode) {
                $storeId  = $entity->getStoreId();
                $store    = $storeId ? Mage::app()->getStore($storeId) : Mage::app()->getStore();

                $taxValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(),
                    $taxAttributeCode, $store);
            }

            $price    = $item->getData('price');
            $qty      = $item->getQty() ?: $item->getQtyOrdered();;
            $rowTotal = $item->getData('row_total');

            //Calculate Percentage. The heart of logic.
            $rowPercentage =  $rowTotal / $subTotal;

            $discountPerUnit = $rowPercentage * $grandDiscount / $qty;
            $priceWithDiscount = $this->slyFloor($price + $discountPerUnit);

            $entityItem = [
                'price' => round($priceWithDiscount, 2),
                'name' => $item->getName(),
                'quantity' => round($qty, 2),
                'sum' => round($priceWithDiscount * $qty, 2),
                'tax' => $taxValue,
            ];

            $percentageSum += $rowPercentage;

            $generalHelper->addLog("Item calculation details:");
            $generalHelper->addLog("Item id: {$item->getId()}. Orig price: {$price} Item rowTotal: {$item->getRowTotal()} Percentage: $rowPercentage. Price of 1 piece: {$priceWithDiscount}. Result of calc:");
            $generalHelper->addLog($entityItem);

            $itemsFinal[$item->getSku()] = $entityItem;
            $itemsSum += $entityItem['sum'];
        }

        $generalHelper->addLog("Sum of all percentages: {$percentageSum}");

        //Calculate DIFF!
        $itemsSumDiff = $grandTotal - $itemsSum - $shippingAmount;

        $generalHelper->addLog("Items sum: {$itemsSum}. Original sum of entity With All Discount: {$sumWithAllDiscount} Diff value: {$itemsSumDiff}");
        if(bccomp($itemsSumDiff, 0.00, 2) < 0) {
            //if: $itemsSumDiff < 0
            $generalHelper->addLog("Notice: Sum of all items is greater than sumWithAllDiscount of entity. ItemsSumDiff: {$itemsSumDiff}");
            $itemsSumDiff = 0.0;
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origSum'        => $sumWithAllDiscount,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingItem = [
            'name'      => $entity->getOrder()->getShippingDescription(),
            'price'     => $entity->getShippingAmount() + $itemsSumDiff,
            'quantity'  => 1.0,
            'sum'       => $entity->getShippingAmount() + $itemsSumDiff,
            'tax'       => $shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)){
            $generalHelper->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }
        $generalHelper->addLog("Final result of recalculation:");
        $generalHelper->addLog($receipt);
        $generalHelper->addLog("== STOP == Recalculation of entity prices. ");

        return $receipt;
    }

    /**Validation method. It sums up all items and compares it to grandTotal.
     * @param array $receipt
     * @return bool True if all items price equal to grandTotal. False - if not.
     */
    protected function _checkReceipt(array $receipt)
    {
        $sum = array_reduce($receipt['items'], function($carry, $item) {
            $carry += $item['sum'];
            return $carry;
        });

        return bcsub($sum, $receipt['origGrandTotal'], 2) === '0.00';
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
}
