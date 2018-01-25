<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountGeneralTestCase extends PHPUnit_Framework_TestCase
{

    protected $discountHelp = null;

    public static function setUpBeforeClass()
    {
        if (!class_exists('Varien_Object')) {
            throw new Exception('Varien_Object class not found.');
        }
    }

    protected function setUp()
    {
        $this->discountHelp = new Mygento_Kkm_Helper_Discount();
    }

    protected function getNewOrderInstance($subTotalInclTax, $grandTotal, $shippingInclTax, $rewardPoints = 0.00)
    {
        $order = new Varien_Object();

        $order->setData('subtotal_incl_tax', $subTotalInclTax);
        $order->setData('grand_total', $grandTotal);
        $order->setData('shipping_incl_tax', $shippingInclTax);
        $order->setData('discount_amount', $grandTotal + $rewardPoints - $subTotalInclTax - $shippingInclTax);

        return $order;
    }

    public function getItem($rowTotalInclTax, $priceInclTax, $discountAmount, $qty = 1, $name = null)
    {
        static $id = 100500;
        $id++;

        $coreHelper = Mage::helper('core');

        $name = $name ?: $coreHelper->getRandomString(8);

        $item = new Varien_Object();
        $item->setData('id', $id);
        $item->setData('row_total_incl_tax', $rowTotalInclTax);
        $item->setData('price_incl_tax', $priceInclTax);
        $item->setData('discount_amount', $discountAmount);
        $item->setData('qty', $qty);
        $item->setData('name', $name);

        return $item;
    }
    public function addItem($order, $item)
    {
        $items   = (array)$order->getData('all_items');
        $items[] = $item;

        $order->setData('all_items', $items);
    }
}
