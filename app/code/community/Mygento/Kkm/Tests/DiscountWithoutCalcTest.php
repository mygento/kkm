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
    public function testCalculation($order, $expectedArray, $key = null)
    {
        parent::testCalculation($order, $expectedArray);

        $this->assertTrue(method_exists($this->discountHelp, 'getRecalculated'));

        $recalculatedData = $this->discountHelp->getRecalculated($order, 'vat18');

        $this->assertEquals($recalculatedData['sum'], $expectedArray['sum'], 'Total sum failed');
        $this->assertEquals($recalculatedData['origGrandTotal'], $expectedArray['origGrandTotal']);

        $this->assertArrayHasKey('items', $recalculatedData);

        $recalcItems         = array_values($recalculatedData['items']);
        $recalcExpectedItems = array_values($expectedArray['items']);

        foreach ($recalcItems as $index => $recalcItem) {
            $this->assertEquals($recalcExpectedItems[$index]['price'], $recalcItem['price'], 'Price of item failed');
            $this->assertEquals($recalcExpectedItems[$index]['quantity'], $recalcItem['quantity']);
//            $this->assertEquals($recalcExpectedItems[$index]['sum'], $recalcItem['sum'], 'Sum of item failed');

            $sumEqual = bccomp($recalcExpectedItems[$index]['sum'], $recalcItem['sum']);
            $this->assertEquals($sumEqual, 0, 'Sum of item failed');
        }
    }

    /** Для этой группы тестов - если есть глобальная скидка, то мы все равно пытаемся её распределить между позициями
     * @return mixed
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected static function getExpected()
    {
        $actualData[parent::TEST_CASE_NAME_1] = [
            'sum'            => 12069.30,
            'origGrandTotal' => 12069.30,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 11691,
                        ],
                        1          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 378.30,
                        ],
                        2          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 0,
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                        ]
                ]
        ];

        $actualData[parent::TEST_CASE_NAME_2] = [
            'sum'            => 5086.19,
            'origGrandTotal' => 9373.19,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 5054.4,
                        ],
                        153        => [
                        'price'    => 1,
                        'quantity' => 3,
                        'sum'      => 31.79,
                        ],
                        'shipping' => [
                        'price'    => 4287.00,
                        'quantity' => 1,
                        'sum'      => 4287.00,
                        ]
                ]
        ];

        $actualData[parent::TEST_CASE_NAME_3] = [
            'sum'            => 5086.19,
            'origGrandTotal' => 5106.19,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 5015.28,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 1,
                            'quantity' => 3,
                            'sum'      => 70.91,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 20,
                            'quantity' => 1,
                            'sum'      => 20,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_4] = [
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

        $actualData[parent::TEST_CASE_NAME_5] = [
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

        $actualData[parent::TEST_CASE_NAME_6] = [
            'sum'            => 702.1,
            'origGrandTotal' => 702.1,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'quantity' => 3,
                            'sum'      => 101,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 1,
                            'quantity' => 4,
                            'sum'      => 101.1,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 1,
                            'quantity' => 5,
                            'sum'      => 500,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_7] = [
            'sum'            => 11691,
            'origGrandTotal' => 11691,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 11691,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_8] = [
            'sum'            => 11610.99,
            'origGrandTotal' => 11611,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 11591.15,
                            'quantity' => 1,
                            'sum'      => 11591.15,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 19.84,
                            'quantity' => 1,
                            'sum'      => 19.84,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0.01,
                            'quantity' => 1,
                            'sum'      => 0.01,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_9] = [
            'sum'            => 12890,
            'origGrandTotal' => 12890,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 12890,
                            'quantity' => 1,
                            'sum'      => 12890,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_10] = [
            'sum'            => 12909.98,
            'origGrandTotal' => 12909.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 12890.14,
                            'quantity' => 1,
                            'sum'      => 12890.14,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 19.84,
                            'quantity' => 1,
                            'sum'      => 19.84,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0.01,
                            'quantity' => 1,
                            'sum'      => 0.01,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_11] = [
            'sum'            => 32130.01,
            'origGrandTotal' => 32130.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 19990,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 1,
                            'quantity' => 500,
                            'sum'      => 9500,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 1000.01,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 1,
                            'quantity' => 4,
                            'sum'      => 1640,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_12] = [
            'sum'            => 13188.99,
            'origGrandTotal' => 13189.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 7989.99,
                            'quantity' => 1,
                            'sum'      => 7989.99,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 1,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_13] = [
            'sum'            => 5199,
            'origGrandTotal' => 5199.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 16.82,
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 19.88,
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0.99,
                            'quantity' => 1,
                            'sum'      => 0.99,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_14] = [
            'sum'            => 13189.01,
            'origGrandTotal' => 13190.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 7990.01,
                            'quantity' => 1,
                            'sum'      => 7990.01,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 1,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_15] = [
            'sum'            => 13188.96,
            'origGrandTotal' => 13189.69,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 7989.96,
                            'quantity' => 1,
                            'sum'      => 7989.96,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0.73,
                            'quantity' => 1,
                            'sum'      => 0.73,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_16] = [
            'sum' => 5188.4,
            'origGrandTotal' => 5190.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 18.31,
                            'quantity' => 40,
                            'sum'      => 732.4,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.3,
                            'quantity' => 30,
                            'sum'      => 549,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 14.75,
                            'quantity' => 40,
                            'sum'      => 590,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.76,
                            'quantity' => 50,
                            'sum'      => 738,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 18.31,
                            'quantity' => 30,
                            'sum'      => 549.3,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.26,
                            'quantity' => 10,
                            'sum'      => 182.6,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.33,
                            'quantity' => 50,
                            'sum'      => 916.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 16.74,
                            'quantity' => 10,
                            'sum'      => 167.4,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 18.31,
                            'quantity' => 20,
                            'sum'      => 366.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 19.85,
                            'quantity' => 20,
                            'sum'      => 397,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1.61,
                            'quantity' => 1,
                            'sum'      => 1.61,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_17] = [
            'sum'            => 7989.99,
            'origGrandTotal' => 7989.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 0,
                            'quantity' => 100,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 7989.99,
                            'quantity' => 1,
                            'sum'      => 7989.99,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_18] = [
            'sum'            => 4297.34,
            'origGrandTotal' => 4297.34,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1,
                            'name'     => 'ku70JISE',
                            'quantity' => 1,
                            'sum'      => 531.66,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 1,
                            'name'     => 'mjuOyj35',
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 1,
                            'name'     => 'qOIarnkS',
                            'quantity' => 1,
                            'sum'      => 790.62,
                            'tax'      => 'vat18',
                        ],
                    3          =>
                        [
                            'price'    => 1,
                            'name'     => 'aFVDa1nu',
                            'quantity' => 1,
                            'sum'      => 2612.25,
                            'tax'      => 'vat18',
                        ],
                    4          =>
                        [
                            'price'    => 1,
                            'name'     => 'uHUim1HY',
                            'quantity' => 1,
                            'sum'      => 362.81,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'name'     => '',
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_19] = [
            'sum'            => 14671.65,
            'origGrandTotal' => 14671.6,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 1144.58,
                            'name'     => 'gVNPUJl8',
                            'quantity' => 5,
                            'sum'      => 5722.9,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 2801.86,
                            'name'     => 'xFWbm8aX',
                            'quantity' => 3,
                            'sum'      => 8405.58,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 543.17,
                            'name'     => 'dgZoOh0z',
                            'quantity' => 1,
                            'sum'      => 543.17,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'name'     => '',
                            //Accordingly to current algorithms it is expected result
                            'price'    => -0.05,
                            'quantity' => 1,
                            'sum'      => -0.05,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_20] = [
            'sum'            => 14160.01,
            'origGrandTotal' => 14160.01,
            'items'          =>
                [
                    100589     =>
                        [
                            'price'    => 1,
                            'name'     => 'jQsjjwT1',
                            'quantity' => 100,
                            'sum'      => 2900.01,
                            'tax'      => 'vat18',
                        ],
                    100590     =>
                        [
                            'price'    => 1,
                            'name'     => 'FfTKzBZx',
                            'quantity' => 50,
                            'sum'      => 2000,
                            'tax'      => 'vat18',
                        ],
                    100591     =>
                        [
                            'price'    => 1,
                            'name'     => 'bUyHtZfc',
                            'quantity' => 50,
                            'sum'      => 1450,
                            'tax'      => 'vat18',
                        ],
                    100592     =>
                        [
                            'price'    => 1,
                            'name'     => 'cSdM2X5h',
                            'quantity' => 30,
                            'sum'      => 1080,
                            'tax'      => 'vat18',
                        ],
                    100593     =>
                        [
                            'price'    => 1,
                            'name'     => '6MMkDWpV',
                            'quantity' => 1,
                            'sum'      => 3990,
                            'tax'      => 'vat18',
                        ],
                    100594     =>
                        [
                            'price'    => 1,
                            'name'     => 'XOQ4bPZ7',
                            'quantity' => 50,
                            'sum'      => 2000,
                            'tax'      => 'vat18',
                        ],
                    100595     =>
                        [
                            'price'    => 1,
                            'name'     => 'kUlCEqDY',
                            'quantity' => 20,
                            'sum'      => 740,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'name'     => '',
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_21] = [
            'sum'            => 17431.01,
            'origGrandTotal' => 17431.01,
            'items'          =>
                [
                    100596     =>
                        [
                            'price'    => 1,
                            'quantity' => 1,
                            'sum'      => 1,
                            'tax'      => 'vat18',
                        ],
                    100597     =>
                        [
                            'price'    => 1,
                            'quantity' => 30,
                            'sum'      => 870.01,
                            'tax'      => 'vat18',
                        ],
                    100598     =>
                        [
                            'price'    => 1,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100599     =>
                        [
                            'price'    => 1,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100600     =>
                        [
                            'price'    => 1,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100601     =>
                        [
                            'price'    => 1,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100602     =>
                        [
                            'price'    => 1,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100603     =>
                        [
                            'price'    => 1,
                            'quantity' => 10,
                            'sum'      => 360,
                            'tax'      => 'vat18',
                        ],
                    100604     =>
                        [
                            'price'    => 1,
                            'quantity' => 60,
                            'sum'      => 1740,
                            'tax'      => 'vat18',
                        ],
                    100605     =>
                        [
                            'price'    => 1,
                            'quantity' => 80,
                            'sum'      => 2320,
                            'tax'      => 'vat18',
                        ],
                    100606     =>
                        [
                            'price'    => 1,
                            'quantity' => 30,
                            'sum'      => 990,
                            'tax'      => 'vat18',
                        ],
                    100607     =>
                        [
                            'price'    => 1,
                            'quantity' => 20,
                            'sum'      => 660,
                            'tax'      => 'vat18',
                        ],
                    100608     =>
                        [
                            'price'    => 1,
                            'quantity' => 10,
                            'sum'      => 330,
                            'tax'      => 'vat18',
                        ],
                    100609     =>
                        [
                            'price'    => 1,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100610     =>
                        [
                            'price'    => 1,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100611     =>
                        [
                            'price'    => 1,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100612     =>
                        [
                            'price'    => 1,
                            'quantity' => 4,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => '',
                        ],
                ],
        ];

        return $actualData;
    }
}
