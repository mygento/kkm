<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountWithoutCalcTest extends DiscountGeneralTestCase
{

    protected function setUp()
    {
        $this->discountHelp = new Mygento_Kkm_Helper_Discount();
        $this->discountHelp->setDoCalculation(false);
    }

    /**
     * Attention! Order of items in array is important!
     * @dataProvider dataProviderOrdersForCheckCalculation
     */
    public function testCalculation($order, $expectedArray)
    {
        $this->assertTrue(method_exists($this->discountHelp, 'getRecalculated'));

        $recalculatedData = $this->discountHelp->getRecalculated($order, 'vat18');

//        echo '<pre>';
//        var_dump($recalculatedData);
//        echo '</pre>';
//        die();

        $this->assertEquals($recalculatedData['sum'], $expectedArray['sum'], 'Total sum failed');
        $this->assertEquals($recalculatedData['origGrandTotal'], $expectedArray['origGrandTotal']);

        $this->assertArrayHasKey('items', $recalculatedData);

        $recalcItems         = array_values($recalculatedData['items']);
        $recalcExpectedItems = array_values($expectedArray['items']);

        foreach ($recalcItems as $index => $recalcItem) {
            $this->assertEquals($recalcExpectedItems[$index]['price'], $recalcItem['price'], 'Price of item failed');
            $this->assertEquals($recalcExpectedItems[$index]['quantity'], $recalcItem['quantity']);
//            $this->assertEquals($recalcExpectedItems[$index]['sum'], $recalcItem['sum'], 'Sum of item failed');

            //TODO: check is it correct for test?
            $sumEqual = bccomp($recalcExpectedItems[$index]['sum'], $recalcItem['sum']);
            $this->assertEquals($sumEqual, 0, 'Sum of item failed');
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
            'sum' => 12069.30,
            'origGrandTotal' => 12069.30,
            'items' =>
                [
                    0 =>
                        [
                            'price' => 1,
                            'quantity' => 1,
                            'sum' => 11691,
                        ],
                        1 =>
                        [
                            'price' => 1,
                            'quantity' => 1,
                            'sum' => 378.30,
                        ],
                        2 =>
                        [
                            'price' => 1,
                            'quantity' => 1,
                            'sum' => 0,
                        ],
                        'shipping' =>
                        [
                            'price' => 0,
                            'quantity' => 1,
                            'sum' => 0,
                        ]
                ]
        ];

        $final['#case 1. Скидки только на товары. Все делится нацело. Bug 1 kop. Товары по 1 шт. Два со скидками, а один бесплатный.'] = [$order, $finalArray];

        $order = $this->getNewOrderInstance(5125.8600, 9373.1900, 4287.00);
        $this->addItem($order, $this->getItem(5054.4000, 5054.4000, 0.0000));
        $this->addItem($order, $this->getItem(71.4600, 23.8200, 39.6700, 3));
        $finalArray = [
            'sum' => 5086.19,
            'origGrandTotal' => 9373.19,
            'items' =>
                [
                    152 =>
                        [
                            'price' => 1,
                            'quantity' => 1,
                            'sum' => 5054.4,
                        ],
                        153 => [
                        'price' => 1,
                        'quantity' => 3,
                        'sum' => 31.79,
                        ],
                        'shipping' => [
                        'price' => 4287.00,
                        'quantity' => 1,
                        'sum' => 4287.00,
                        ]
                ]
        ];

        $final['#case 2. Скидка на весь чек и на отдельный товар. Order 145000128 DemoEE'] = [$order, $finalArray];


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
                    'price'    => 1,
                    'quantity' => 2,
                    'sum'      => 1000.82,
                ],
                153        => [
                    'price'    => 1,
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
                    '152_1'    =>
                        [
                            'price'    => 1,
                            'quantity' => 3,
                            'sum'      => 101,
                        ],
                        '153_1'    =>
                        [
                            'price'    => 1,
                            'quantity' => 4,
                            'sum'      => 101.01,
                        ],
                        'shipping' => [
                        'price'    => 0.0,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ]
                ]
        ];

        $final['#case 5. Скидки только на товары. Не делятся нацело.'] = [$order, $finalArray];

        //Reward Points included
        $order = $this->getNewOrderInstance(13010.0000, 11611.0000, 0, 100);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000, 1));
        $this->addItem($order, $this->getItem(20.0000, 20.0000, 0.0000, 1));
        $finalArray = [
            'sum'            => 11611.0,
            'origGrandTotal' => 11611.0,
            'items'          =>
                [
                    152        =>
                    [
                        'price'    => 11591.15,
                        'quantity' => 1,
                        'sum'      => 11591.15,
                    ],
                    153        =>
                    [
                        'price'    => 19.85,
                        'quantity' => 1,
                        'sum'      => 19.85,
                    ],
                    'shipping' => [
                    'price'    => 0.00,
                    'quantity' => 1,
                    'sum'      => 0.00,
                    ],
                ],
        ];

        $final['#case 6. Reward points в заказе'] = [$order, $finalArray];

        return $final;
    }
}
