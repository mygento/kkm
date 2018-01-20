<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountSplitItemsTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        if (!class_exists('Varien_Object')) {
            throw new Exception('Varien_Object class not found.');
        }
    }

    /**
     * Attention! Order of items in array is important!
     * @dataProvider dataProviderOrdersForCheckCalculation
     */
    public function testCalculation($order, $expectedArray)
    {
        $discountHelp = new Mygento_Kkm_Helper_Discount();
        $discountHelp->setIsSplitItemsAllowed(true);

        $this->assertTrue(method_exists($discountHelp, 'getRecalculated'));

        $recalculatedData = $discountHelp->getRecalculated($order, 'vat18');

        $this->assertEquals($recalculatedData['sum'], $expectedArray['sum'], 'Total sum failed');
        $this->assertEquals($recalculatedData['origGrandTotal'], $expectedArray['origGrandTotal']);

        $this->assertArrayHasKey('items', $recalculatedData);

        $recalcItems         = array_values($recalculatedData['items']);
        $recalcExpectedItems = array_values($expectedArray['items']);

        foreach ($recalcItems as $index => $recalcItem) {
            $this->assertEquals($recalcExpectedItems[$index]['price'], $recalcItem['price'], 'Price of item failed');
            $this->assertEquals($recalcExpectedItems[$index]['quantity'], $recalcItem['quantity']);
            $this->assertEquals($recalcExpectedItems[$index]['sum'], $recalcItem['sum'], 'Sum of item failed');
        }
    }

    /** Test splitting item mechanism
     *
     * @dataProvider dataProviderItemsForSplitting
     */
    public function testProcessedItem($item, $expectedArray)
    {
        $discountHelp = new Mygento_Kkm_Helper_Discount();
        $discountHelp->setIsSplitItemsAllowed(true);

        $split = $discountHelp->getProcessedItem($item);

//        var_export($split);
//        die();

        $this->assertEquals(count($split), count($expectedArray), 'Item was not splitted correctly!');

        $i = 0;
        foreach ($split as $item) {
            $this->assertEquals($expectedArray[$i]['price'], $item['price'], 'Price of item failed');
            $this->assertEquals($expectedArray[$i]['quantity'], $item['quantity']);
            $this->assertEquals($expectedArray[$i]['sum'], $item['sum'], 'Sum of item failed');

            $i++;
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function dataProviderOrdersForCheckCalculation()
    {
        $final = [];

        $order = $this->getNewOrderInstance(13380.0000, 12069.3000, 0.0000);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000));
        $this->addItem($order, $this->getItem(390.0000, 390.0000, 11.7000));
        $this->addItem($order, $this->getItem(0.0000, 0.0000, 0.0000));
        //Bug 1 kop. Товары по 1 шт. Два со скидками, а один бесплатный. Цены делятся нацело - пересчет не должен применяться.
        $finalArray = [
            'sum'            => 12069.30,
            'origGrandTotal' => 12069.30,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11691,
                            'quantity' => 1,
                            'sum'      => 11691,
                        ],
                        153        => [
                        'price'    => 378.30,
                        'quantity' => 1,
                        'sum'      => 378.30,
                        ],
                        154        => [
                        'price'    => 0,
                        'quantity' => 1,
                        'sum'      => 0,
                        ],
                        'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0,
                        ],
                ],
        ];

        $final['#case 1. Скидки только на товары. Все делится нацело. Bug 1 kop. Товары по 1 шт. Два со скидками, а один бесплатный.'] = [$order, $finalArray];

        $order = $this->getNewOrderInstance(5125.8600, 9373.1900, 4287.00);
        $this->addItem($order, $this->getItem(5054.4000, 5054.4000, 0.0000));
        $this->addItem($order, $this->getItem(71.4600, 23.8200, 39.6700, 3));
        $finalArray = [
            'sum'            => 5086.19,
            'origGrandTotal' => 9373.19,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 5054.4,
                            'quantity' => 1,
                            'sum'      => 5054.4,
                        ],
                    '153_1'        => [
                        'price'    => 10.6,
                        'quantity' => 2,
                        'sum'      => 21.2,
                        ],
                    '154_2'        => [
                        'price'    => 10.59,
                        'quantity' => 1,
                        'sum'      => 10.59,
                        ],
                    'shipping' => [
                        'price'    => 4287.00,
                        'quantity' => 1,
                        'sum'      => 4287.00,
                        ],
                ],
        ];

        $final['#case 2. Скидка на весь чек и на отдельный товар. Order 145000128 DemoEE'] = [$order, $finalArray];

        //39.67 Discount на весь заказ
        $order = $this->getNewOrderInstance(5125.8600, 9373.1900, 4287.00);
        $this->addItem($order, $this->getItem(5054.4000, 5054.4000, 0.0000));
        $this->addItem($order, $this->getItem(71.4600, 23.8200, 0.0000, 3));
        $finalArray = [
            'sum'            => 5086.17,
            'origGrandTotal' => 9373.19,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 5015.28,
                            'quantity' => 1,
                            'sum'      => 5015.28,
                        ],
                    153        => [
                        'price'    => 23.63,
                        'quantity' => 3,
                        'sum'      => 70.89,
                        ],
                    'shipping' => [
                        'price'    => 4287.02,
                        'quantity' => 1,
                        'sum'      => 4287.02,
                        ],
                ],
        ];

//TODO: Здесь вопрос - 153 позиция разделяется на 2. Норм это или не норм ?
        $final['#case 3. Скидка только на весь чек'] = [$order, $finalArray];

        $order = $this->getNewOrderInstance(5000.8600, 5200.8600, 200.00);
        $this->addItem($order, $this->getItem(1000.8200, 500.4100, 0.0000, 2));
        $this->addItem($order, $this->getItem(4000.04, 1000.01, 0.0000, 4));
        $finalArray = [
            'sum'            => 5000.86,
            'origGrandTotal' => 5200.86,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 500.41,
                            'quantity' => 2,
                            'sum'      => 1000.82,
                        ],
                    153        => [
                        'price'    => 1000.01,
                        'quantity' => 4,
                        'sum'      => 4000.04,
                        ],
                    'shipping' => [
                        'price'    => 200,
                        'quantity' => 1,
                        'sum'      => 200,
                        ],
                ],
        ];

        $final['#case 4. Нет скидок никаких'] = [$order, $finalArray];

        $order = $this->getNewOrderInstance(222, 202.1, 0);
        $this->addItem($order, $this->getItem(120, 40, 19, 3));
        $this->addItem($order, $this->getItem(102, 25.5, 0.9, 4));
        $finalArray = [
            'sum'            => 202.1,
            'origGrandTotal' => 202.1,
            'items'          =>
                [
                    '152_1'        =>
                        [
                            'price'    => 36.42,
                            'quantity' => 1,
                            'sum'      => 36.42,
                        ],
                    '152_2'        =>
                        [
                            'price'    => 36.41,
                            'quantity' => 2,
                            'sum'      => 72.82,
                        ],
                    '153_1'        =>
                        [
                            'price'    => 23.22,
                            'quantity' => 2,
                            'sum'      => 46.44,
                        ],
                    '153_2'        =>
                        [
                            'price'    => 23.21,
                            'quantity' => 2,
                            'sum'      => 46.42,
                        ],
                    'shipping' => [
                        'price'    => 0.0,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $final['#case 5. Скидки только на товары. Не делятся нацело.'] = [$order, $finalArray];

        $order = $this->getNewOrderInstance(722, 702.1, 0);
        $this->addItem($order, $this->getItem(120, 40, 19, 3));
        $this->addItem($order, $this->getItem(102, 25.5, 0.9, 4));
        $this->addItem($order, $this->getItem(500, 100, 0, 5));
        
        $finalArray = [
            'sum'            => 702.1,
            'origGrandTotal' => 702.1,
            'items'          =>
                [
                    '152_1'        =>
                        [
                            'price'    => 36.42,
                            'quantity' => 1,
                            'sum'      => 36.42,
                        ],
                    '152_2'        =>
                        [
                            'price'    => 36.41,
                            'quantity' => 2,
                            'sum'      => 72.82,
                        ],
                    '153_1'        =>
                        [
                            'price'    => 23.22,
                            'quantity' => 2,
                            'sum'      => 46.44,
                        ],
                    '153_2'        =>
                        [
                            'price'    => 23.21,
                            'quantity' => 2,
                            'sum'      => 46.42,
                        ],
                    154        =>
                        [
                            'price'    => 100,
                            'quantity' => 5,
                            'sum'      => 500,
                        ],
                    'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $final['#case 6. Есть позиция, на которую НЕ ДОЛЖНА распространиться скидка.'] = [$order, $finalArray];

        //Bug GrandTotal заказа меньше, чем сумма всех позиций. На 1 товар скидка 100%
        $order = $this->getNewOrderInstance(13010.0000, 11691.0000, 0);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000, 1));
        $this->addItem($order, $this->getItem(20.0000, 20.0000, 20.0000, 1));
        $finalArray = [
            'sum'            => 11691.0,
            'origGrandTotal' => 11691.0,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11691.0,
                            'quantity' => 1,
                            'sum'      => 11691.0,
                        ],
                    153        =>
                        [
                            'price'    => 0.0,
                            'quantity' => 1,
                            'sum'      => 0.0,
                        ],
                    'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $final['#case 7. Bug grandTotal < чем сумма всех позиций. Есть позиция со 100% скидкой.'] = [$order, $finalArray];

        return $final;
    }

        /**
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function dataProviderItemsForSplitting() {
        $final = [];

        // #1 rowDiff = 2 kop. qty = 3. qtyUpdate = 3
        $item = $this->getItem(0, 0, 0, 3);
        $item->setData(Mygento_Kkm_Helper_Discount::NAME_ROW_DIFF, 2);
        $item->setData(Mygento_Kkm_Helper_Discount::NAME_UNIT_PRICE, 10.59);

        $expected = [
            [
                'price' => 10.6,
                'quantity' => 2,
                'sum' => 21.2,
                'tax' => NULL,
            ],
            [
                'price' => 10.59,
                'quantity' => 1,
                'sum' => 10.59,
                'tax' => NULL,
            ]
        ];
        $final['#case 1. 2 копейки распределить по 3м товарам.'] = [$item, $expected];

        // #2 rowDiff = 150 kop. qty = 30. qtyUpdate = 0
        $item2 = $this->getItem(0, 0, 0, 30);
        $item2->setData(Mygento_Kkm_Helper_Discount::NAME_ROW_DIFF, 150);
        $item2->setData(Mygento_Kkm_Helper_Discount::NAME_UNIT_PRICE, 10);

        $expected2 = [
            [
                'price' => 10.05,
                'quantity' => 30,
                'sum' => 301.5,
            ]
        ];
        $final['#case 2. 150 копеек распределить по 30 товарам.'] = [$item2, $expected2];

        // #3 rowDiff = 5 kop. qty = 3. qtyUpdate = 2
        $item3 = $this->getItem(0, 0, 0, 3);
        $item3->setData(Mygento_Kkm_Helper_Discount::NAME_ROW_DIFF, 5);
        $item3->setData(Mygento_Kkm_Helper_Discount::NAME_UNIT_PRICE, 10);

        $expected3 = [
            [
                'price' => 10.02,
                'quantity' => 2,
                'sum' => 20.04,
            ],
            [
                'price' => 10.01,
                'quantity' => 1,
                'sum' => 10.01,
            ],
        ];
        $final['#case 3. 5 копеек распределить по 3 товарам.'] = [$item3, $expected3];

        return $final;
    }

    //TODO: Move common methods to abstract test class and extend it
    
    /**
     *
     * @staticvar int $id
     * @param type $rowTotalInclTax
     * @param type $priceInclTax
     * @param type $discountAmount
     * @param type $qty optional 1 by default
     * @param type $name optional
     * @return \Varien_Object
     */
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

    /**
     *
     * @param type $subTotalInclTax
     * @param type $grandTotal
     * @param type $shippingInclTax
     * @return \Varien_Object
     */
    protected function getNewOrderInstance($subTotalInclTax, $grandTotal, $shippingInclTax)
    {
        $order = new Varien_Object();

        $order->setData('subtotal_incl_tax', $subTotalInclTax);
        $order->setData('grand_total', $grandTotal);
        $order->setData('shipping_incl_tax', $shippingInclTax);

        return $order;
    }

    public function addItem($order, $item)
    {
        $items   = (array)$order->getData('all_items');
        $items[] = $item;

        $order->setData('all_items', $items);
    }
}
