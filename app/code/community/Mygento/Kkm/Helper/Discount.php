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

    const VERSION = '1.0.13';

    protected $generalHelper = null;

    protected $_entity           = null;
    protected $_taxValue         = null;
    protected $_taxAttributeCode = null;
    protected $_shippingTaxValue = null;

    protected $_discountlessSum = 0.00;

    /** @var bool Does item exist with price not divisible evenly? Есть ли item, цена которого не делится нацело */
    protected $_wryItemUnitPriceExists = false;

    /** @var bool Возможность разделять одну товарную позицию на 2, если цена не делится нацело */
    protected $isSplitItemsAllowed = false;

    /** @var bool Включить перерасчет? */
    protected $doCalculation = true;

    /** @var bool Размазывать ли скидку по всей позициям? */
    protected $spreadDiscOnAllUnits = false;

    const NAME_UNIT_PRICE = 'disc_hlpr_price';
    const NAME_ROW_DIFF   = 'recalc_row_diff';

    /** Returns all items of the entity (order|invoice|creditmemo) with properly calculated discount and properly calculated Sum
     * @param $entity Mage_Sales_Model_Order | Mage_Sales_Model_Order_Invoice | Mage_Sales_Model_Order_Creditmemo
     * @param string $taxValue
     * @param string $taxAttributeCode Set it if info about tax is stored in product in certain attr
     * @param string $shippingTaxValue
     * @return array with calculated items and sum
     */
    public function getRecalculated($entity, $taxValue = '', $taxAttributeCode = '', $shippingTaxValue = '')
    {
        if (!$entity) {
            return;
        }

        $this->_entity              = $entity;
        $this->_taxValue            = $taxValue;
        $this->_taxAttributeCode    = $taxAttributeCode;
        $this->_shippingTaxValue    = $shippingTaxValue;
        $this->generalHelper        = Mage::helper($this->_code);

        $globalDiscount = $this->getGlobalDiscount();

        $this->generalHelper->addLog("== START == Recalculation of entity prices. Helper Version: " . self::VERSION . ".  Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");
        $this->generalHelper->addLog("Do calculation: " . ($this->doCalculation ? 'Yes' : 'No'));
        $this->generalHelper->addLog("Spread discount: " . ($this->spreadDiscOnAllUnits ? 'Yes' : 'No'));
        $this->generalHelper->addLog("Split items: " . ($this->isSplitItemsAllowed ? 'Yes' : 'No'));

        //Если есть RewardPoints - то калькуляцию применять необходимо принудительно
        if ($globalDiscount !== 0.00) {
            $this->doCalculation       = true;
            $this->generalHelper->addLog("SplitItems and DoCalculation set to true because of global Discount (e.g. reward points)");
        }

        switch (true) {
            case (!$this->doCalculation):
                $this->generalHelper->addLog("No calculation at all.");
                break;
            case ($this->checkSpread()):
                $this->applyDiscount();
                $this->generalHelper->addLog("'Apply Discount' logic was applied");
                break;
            default:
                //Это случай, когда не нужно размазывать копейки по позициям
                //и при этом, позиции могут иметь скидки, равномерно делимые.

                $this->setSimplePrices();
                $this->generalHelper->addLog("'Simple prices' logic was applied");
                break;
        }

        $this->generalHelper->addLog("== STOP == Recalculation. Entity class: " . get_class($entity) . ". Entity id: {$entity->getId()}");

        return $this->buildFinalArray();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function applyDiscount()
    {
        $subTotal       = $this->_entity->getData('subtotal_incl_tax');
        $discount       = $this->_entity->getData('discount_amount');

        /** @var float $superGrandDiscount Скидка на весь заказ. Например, rewardPoints или storeCredit */
        $superGrandDiscount = $this->getGlobalDiscount();

        //Bug NN-347. -1 коп в доставке, если Magento неверно посчитала grandTotal заказа
        if ($superGrandDiscount && abs($superGrandDiscount) < 10.00) {
            $this->preFixLowDiscount();
            $superGrandDiscount = 0.00;
        }
        $grandDiscount = $superGrandDiscount;

        //Если размазываем скидку - то размазываем всё: (скидки товаров + $superGrandDiscount)
        if ($this->spreadDiscOnAllUnits) {
            $grandDiscount = $discount + $this->getGlobalDiscount();
        }

        $percentageSum = 0;

        $items      = $this->getAllItems();
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $price       = $item->getData('price_incl_tax');
            $qty         = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal    = $item->getData('row_total_incl_tax');
            $rowDiscount = round((-1.00) * $item->getDiscountAmount(), 2);

            // ==== Start Calculate Percentage. The heart of logic. ====

            /** @var float $denominator Это знаменатель дроби (rowTotal/сумма).
             * Если скидка должна распространиться на все позиции - то это subTotal.
             * Если же позиции без скидок должны остаться без изменений - то это
             * subTotal за вычетом всех позиций без скидок.*/
            $denominator = $subTotal - $this->_discountlessSum;

            if ($this->spreadDiscOnAllUnits || ($subTotal == $this->_discountlessSum) || ($superGrandDiscount !== 0.00)) {
                $denominator = $subTotal;
            }

            $rowPercentage = $rowTotal / $denominator;

            // ==== End Calculate Percentage. ====

            if (!$this->spreadDiscOnAllUnits && (floatval($rowDiscount) === 0.00) && ($superGrandDiscount === 0.00)) {
                $rowPercentage = 0;
            }
            $percentageSum += $rowPercentage;

            if ($this->spreadDiscOnAllUnits) {
                $rowDiscount = 0;
            }

            $discountPerUnit = $this->slyCeil(($rowDiscount + $rowPercentage * $grandDiscount) / $qty);

            $priceWithDiscount = bcadd($price, $discountPerUnit, 2);

            //Set Recalculated unit price for the item
            $item->setData(self::NAME_UNIT_PRICE, $priceWithDiscount);

            $rowTotalNew = round($priceWithDiscount * $qty, 2);
            $itemsSum += $rowTotalNew;

            $rowDiscountNew = $rowDiscount + round($rowPercentage * $grandDiscount, 2);

            $rowDiff = round($rowTotal + $rowDiscountNew - $rowTotalNew, 2) * 100;

            $item->setData(self::NAME_ROW_DIFF, $rowDiff);
        }

        if ($this->spreadDiscOnAllUnits && $this->isSplitItemsAllowed) {
            $this->postFixLowDiscount();
        }

        $this->generalHelper->addLog("Sum of all percentages: {$percentageSum}");
    }

    /** Возвращает скидку на весь заказ (если есть). Например, rewardPoints или storeCredit.
     * Если нет скидки - возвращает 0.00
     * @return float
     */
    protected function getGlobalDiscount()
    {
        $items = $this->getAllItems();
        $totalItemsSum = 0;
        foreach ($items as $item) {
            $totalItemsSum += $item->getData('row_total_incl_tax');
        }

        $shippingAmount = $this->_entity->getData('shipping_incl_tax');
        $grandTotal     = round($this->_entity->getData('grand_total'), 2);
        $discount       = round($this->_entity->getData('discount_amount'), 2);

        $globDisc = round($grandTotal - $shippingAmount - $totalItemsSum - $discount, 2);

        return $globDisc;
    }

    protected function preFixLowDiscount()
    {
        $items          = $this->getAllItems();
        $globalDiscount = $this->getGlobalDiscount();

        $sign  = $globalDiscount / abs($globalDiscount);
        $i     = abs($globalDiscount) * 100;
        $count = count($items);
        $iter  = 0;

        while ($i > 0) {
            $item = current($items);

//            echo("\n" . 'i:' . $i . "\t\t");

            $itDisc  = $item->getData('discount_amount');
            $itTotal = $item->getData('row_total_incl_tax');

            $inc = $this->getDicsountIncrement($sign * $i, $count, $itTotal, $itDisc);
            $item->setData('discount_amount', $itDisc - $inc / 100);
//            echo('add:' . ($inc/100) . "\t\t");
            $i = intval($i - abs($inc));

            $next = next($items);
            if (!$next) {
                reset($items);
            }
//            echo('inc:' . $inc . "\t\t");
            $iter++;
        }

//        echo(PHP_EOL . PHP_EOL.  'iter:' . $iter . PHP_EOL);

        return $iter;
    }

    protected function postFixLowDiscount()
    {
        $items          = $this->getAllItems();
        $grandTotal     = round($this->_entity->getData('grand_total'), 2);
        $shippingAmount = $this->_entity->getData('shipping_incl_tax');

        $newItemsSum = 0;
        $rowDiffSum  = 0;
        foreach ($items as $item) {
            $rowTotalNew = $item->getData(self::NAME_UNIT_PRICE) * $item->getQty() + ($item->getData(self::NAME_ROW_DIFF) / 100);
            $rowDiffSum  += $item->getData(self::NAME_ROW_DIFF);
            $newItemsSum += $rowTotalNew;
        }

        $lostDiscount = round($grandTotal - $shippingAmount - $newItemsSum, 2);

        $sign  = $lostDiscount / abs($lostDiscount);
        $i     = abs($lostDiscount) * 100;
        $count = count($items);
        $iter  = 0;
        while ($i > 0) {
            $item = current($items);

//            echo("\n" . 'i:' . $i . "\t\t");

            $qty        = $item->getQty() ?: $item->getQtyOrdered();
            $rowDiff    = $item->getData(self::NAME_ROW_DIFF);
            $itTotalNew = $item->getData(self::NAME_UNIT_PRICE) * $qty + $rowDiff / 100;

            $inc = $this->getDicsountIncrement($sign * $i, $count, $itTotalNew, 0);

            $item->setData(self::NAME_ROW_DIFF, $item->getData(self::NAME_ROW_DIFF) + $inc);
//            echo('add:' . ($inc/100) . "\t\t");
            $i = intval($i - abs($inc));

            $next = next($items);
            if (!$next) {
                reset($items);
            }
//            echo('inc:' . $inc . "\t\t");
//            echo('rowDiff:' . $item->getData(self::NAME_ROW_DIFF) . "\t\t");
            $iter++;
        }

//        echo(PHP_EOL . PHP_EOL.  '===== iter: ' . $iter . PHP_EOL);

        return $iter;
    }

    /**
     * @param int $amountToSpread (in kops)
     * @param $itemsCount
     * @param $itemTotal
     * @param $itemDiscount
     */
    public function getDicsountIncrement($amountToSpread, $itemsCount, $itemTotal, $itemDiscount)
    {
        $sign = $amountToSpread / abs($amountToSpread);

        //Пытаемся размазать поровну
        $discPerItem = intval(abs($amountToSpread) / $itemsCount);
        $inc         = ($discPerItem > 1) && ($itemTotal - $itemDiscount) > $discPerItem
            ? $sign * $discPerItem
            : $sign;

        //Изменяем скидку позиции
        if (($itemTotal - $itemDiscount) > abs($inc)) {
            return $inc;
        }

        return 0;
    }

    /**If everything is evenly divisible - set up prices without extra recalculations
     * like applyDiscount() method does.
     *
     */
    public function setSimplePrices()
    {
        $items    = $this->getAllItems();
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $qty               = $item->getQty() ?: $item->getQtyOrdered();
            $rowTotal          = $item->getData('row_total_incl_tax');

            $priceWithDiscount = ($rowTotal - $item->getData('discount_amount')) / $qty;
            $item->setData(self::NAME_UNIT_PRICE, $priceWithDiscount);
        }
    }

    public function buildFinalArray()
    {
        $grandTotal = round($this->_entity->getData('grand_total'), 2);
        $items      = $this->getAllItems();

        $itemsFinal = [];
        $itemsSum   = 0.00;
        foreach ($items as $item) {
            if (!$this->isValidItem($item)) {
                continue;
            }

            $splitedItems = $this->getProcessedItem($item);
            $itemsFinal   = array_merge($itemsFinal, $splitedItems);
        }

        //Calculate sum
        foreach ($itemsFinal as $item) {
            $itemsSum += $item['sum'];
        }

        $receipt = [
            'sum'            => $itemsSum,
            'origGrandTotal' => floatval($grandTotal)
        ];

        $shippingAmount = $this->_entity->getData('shipping_incl_tax') + 0.00;
        $itemsSumDiff   = round($this->slyFloor($grandTotal - $itemsSum - $shippingAmount, 3), 2);

        $this->generalHelper->addLog("Items sum: {$itemsSum}. Shipping increase: {$itemsSumDiff}");

        $shippingItem = [
            'name'     => $this->getShippingName($this->_entity),
            'price'    => $shippingAmount + $itemsSumDiff,
            'quantity' => 1.0,
            'sum'      => $shippingAmount + $itemsSumDiff,
            'tax'      => $this->_shippingTaxValue,
        ];

        $itemsFinal['shipping'] = $shippingItem;
        $receipt['items']       = $itemsFinal;

        if (!$this->_checkReceipt($receipt)) {
            $this->generalHelper->addLog("WARNING: Calculation error! Sum of items is not equal to grandTotal!");
        }

        $this->generalHelper->addLog("Final array:");
        $this->generalHelper->addLog($receipt);

        $receiptObj = (object) $receipt;

        Mage::dispatchEvent('mygento_discount_recalculation_after', array('modulecode' => $this->_code, 'receipt' => $receiptObj));

        return (array)$receiptObj;
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

        if (!$this->doCalculation) {
            $entityItem['sum']   = round($item->getData('row_total_incl_tax') - $item->getData('discount_amount'), 2);
            $entityItem['price'] = 1;
        }

        $generalHelper->addLog("Item calculation details:");
        $generalHelper->addLog("Item id: {$item->getId()}. Orig price: {$price} Item rowTotalInclTax: {$item->getData('row_total_incl_tax')} PriceInclTax of 1 piece: {$price}. Result of calc:");
        $generalHelper->addLog($entityItem);

        return $entityItem;
    }

    /** Make item array and split (if needed) it into 2 items with different prices
     *
     * @param type $item
     * @return array
     */
    public function getProcessedItem($item)
    {
        $generalHelper = Mage::helper($this->_code);

        $final = [];

        $taxValue = $this->_taxAttributeCode ? $this->addTaxValue($this->_taxAttributeCode, $this->_entity, $item) : $this->_taxValue;
        $price    = !is_null($item->getData(self::NAME_UNIT_PRICE)) ? $item->getData(self::NAME_UNIT_PRICE) : $item->getData('price_incl_tax');

        $entityItem = $this->_buildItem($item, $price, $taxValue);

        $rowDiff = $item->getData(self::NAME_ROW_DIFF);

        if (!$rowDiff || !$this->isSplitItemsAllowed || !$this->doCalculation) {
            $final[$item->getId()] = $entityItem;
            return $final;
        }

        $qty = $item->getQty() ?: $item->getQtyOrdered();

        /** @var int $qtyUpdate Сколько товаров из ряда нуждаются в увеличении цены
         *  Если $qtyUpdate =0 - то цена всех товаров должна быть увеличина
         */
        $qtyUpdate = $rowDiff % $qty;

        //2 кейса:
        //$qtyUpdate == 0 - то всем товарам увеличить цену, не разделяя.
        //$qtyUpdate > 0  - считаем сколько товаров будут увеличены

        /** @var int "$inc + 1 коп" На столько должны быть увеличены цены */
        $inc = intval($rowDiff / $qty);

        $generalHelper->addLog("Item {$item->getId()} has rowDiff={$rowDiff}.");
        $generalHelper->addLog("qtyUpdate={$qtyUpdate}. inc={$inc} kop.");

        $item1 = $entityItem;
        $item2 = $entityItem;

        $item1['price'] = $item1['price'] + $inc / 100;
        $item1['quantity'] = $qty - $qtyUpdate;
        $item1['sum'] = round($item1['quantity'] * $item1['price'], 2);

        if ($qtyUpdate == 0) {
            $final[$item->getId()] = $item1;

            return $final;
        }

        $item2['price'] = $item2['price'] + 0.01 + $inc / 100;
        $item2['quantity'] = $qtyUpdate;
        $item2['sum'] = round($item2['quantity'] * $item2['price'], 2);

        $final[$item->getId() . '_1'] = $item1;
        $final[$item->getId() . '_2'] = $item2;

        return $final;
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

    public function slyCeil($val, $precision = 2)
    {
        $factor  = 1.00;
        $divider = pow(10, $precision);

        if ($val < 0) {
            $factor = -1.00;
        }

        return (ceil(abs($val) * $divider) / $divider) * $factor;
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

    /** It checks do we need to spread discount on all units and sets flag $this->spreadDiscOnAllUnits
     * @return nothing
     */
    public function checkSpread()
    {
        $items = $this->getAllItems();

        $this->_discountlessSum = 0.00;
        foreach ($items as $item) {
            $qty      = $item->getQty() ?: $item->getQtyOrdered();
            $rowPrice = $item->getData('row_total_incl_tax') - $item->getData('discount_amount');

            if (floatval($item->getData('discount_amount')) === 0.00) {
                $this->_discountlessSum += $item->getData('row_total_incl_tax');
            }

            /* Означает, что есть item, цена которого не делится нацело*/
            if (!$this->_wryItemUnitPriceExists) {
                $decimals = $this->getDecimalsCountAfterDiv($rowPrice, $qty);

                $this->_wryItemUnitPriceExists = $decimals > 2 ? true : false;
            }
        }


        //Есть ли общая скидка на Чек. bccomp returns 0 if operands are equal
        if (bccomp($this->getGlobalDiscount(), 0.00, 2) !== 0) {
            $this->generalHelper->addLog("1. Global discount on whole cheque.");

            return true;
        }

        //ok, есть товар, который не делится нацело
        if ($this->_wryItemUnitPriceExists) {
            $this->generalHelper->addLog("2. Item with price which is not divisible evenly.");

            return true;
        }

        if ($this->spreadDiscOnAllUnits) {
            $this->generalHelper->addLog("3. SpreadDiscount = Yes.");

            return true;
        }

        return false;
    }

    public function getDecimalsCountAfterDiv($x, $y)
    {
        $divRes   = strval(round($x / $y, 20));
        $decimals = strrchr($divRes, ".") ? strlen(strrchr($divRes, ".")) - 1 : 0;

        return $decimals;
    }

    public function getAllItems()
    {
        return $this->_entity->getAllVisibleItems() ? $this->_entity->getAllVisibleItems() : $this->_entity->getAllItems();
    }

    /**
     * @param bool $isSplitItemsAllowed
     */
    public function setIsSplitItemsAllowed($isSplitItemsAllowed)
    {
        $this->isSplitItemsAllowed = (bool)$isSplitItemsAllowed;
    }

    /**
     * @param bool $doCalculation
     */
    public function setDoCalculation($doCalculation)
    {
        $this->doCalculation = (bool)$doCalculation;
    }

    /**
     * @param null $spreadDiscOnAllUnits
     */
    public function setSpreadDiscOnAllUnits($spreadDiscOnAllUnits)
    {
        $this->spreadDiscOnAllUnits = (bool)$spreadDiscOnAllUnits;
    }
}
