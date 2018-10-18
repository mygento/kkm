<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountSpreadOnAllItemsTest extends DiscountGeneralTestCase
{

    protected function setUp()
    {
        $this->discountHelp = new Mygento_Kkm_Helper_Discount();
        $this->discountHelp->setSpreadDiscOnAllUnits(true);
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
            $this->assertEquals($recalcExpectedItems[$index]['sum'], $recalcItem['sum'], 'Sum of item failed');
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected static function getExpected()
    {
        $actualData[parent::TEST_CASE_NAME_1] =  [
            'sum'            => 12069.29,
            'origGrandTotal' => 12069.30,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11717.5,
                            'quantity' => 1,
                            'sum'      => 11717.5,
                        ],
                        153        => [
                        'price'    => 351.79,
                        'quantity' => 1,
                        'sum'      => 351.79,
                        ],
                        154        => [
                        'price'    => 0,
                        'quantity' => 1,
                        'sum'      => 0,
                        ],
                        'shipping' => [
                        'price'    => 0.01,
                        'quantity' => 1,
                        'sum'      => 0.01,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_2] = [
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
                        '153_1'    => [
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

        $actualData[parent::TEST_CASE_NAME_3] = [
            'sum'            => 5086.17,
            'origGrandTotal' => 5106.19,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5015.28,
                            'name'     => 'EUzlTJZ0',
                            'quantity' => 1,
                            'sum'      => 5015.28,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 23.63,
                            'name'     => 'QoPowh4G',
                            'quantity' => 3,
                            'sum'      => 70.89,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
                            'price'    => 20.02,
                            'quantity' => 1,
                            'sum'      => 20.02,
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

        $actualData[parent::TEST_CASE_NAME_5] = [
            'sum'            => 202.07,
            'origGrandTotal' => 202.1,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 36.41,
                            'quantity' => 3,
                            'sum'      => 109.23
                        ],
                        153        =>
                        [
                            'price'    => 23.21,
                            'quantity' => 4,
                            'sum'      => 92.84
                        ],
                        'shipping' => [
                        'price'    => 0.03,
                        'quantity' => 1,
                        'sum'      => 0.03,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_6] = [
            'sum'            => 702.03,
            'origGrandTotal' => 702.1,
            'items'          =>
                [
                    '152'        =>
                        [
                            'price'    => 38.89,
                            'quantity' => 3,
                            'sum'      => 116.67,
                        ],
                        '153'        =>
                        [
                            'price'    => 24.79,
                            'quantity' => 4,
                            'sum'      => 99.16,
                        ],
                        154        =>
                        [
                            'price'    => 97.24,
                            'quantity' => 5,
                            'sum'      => 486.2,
                        ],
                        'shipping' => [
                        'price'    => 0.07,
                        'quantity' => 1,
                        'sum'      => 0.07,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_7] = [
            'sum'            => 11690.99,
            'origGrandTotal' => 11691.0,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11673.02,
                            'quantity' => 1,
                            'sum'      => 11673.02,
                        ],
                        153        =>
                        [
                            'price'    => 17.97,
                            'quantity' => 1,
                            'sum'      => 17.97,
                        ],
                        'shipping' => [
                        'price'    => 0.01,
                        'quantity' => 1,
                        'sum'      => 0.01,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_8] = [
            'sum'            => 11610.99,
            'origGrandTotal' => 11611.0,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11593.15,
                            'quantity' => 1,
                            'sum'      => 11593.15,
                        ],
                        153        =>
                        [
                            'price'    => 17.84,
                            'quantity' => 1,
                            'sum'      => 17.84,
                        ],
                        'shipping' => [
                        'price'    => 0.01,
                        'quantity' => 1,
                        'sum'      => 0.01,
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
                    152        =>
                        [
                            'price'    => 12890.14,
                            'quantity' => 1,
                            'sum'      => 12890.14,
                        ],
                        153        =>
                        [
                            'price'    => 19.84,
                            'quantity' => 1,
                            'sum'      => 19.84,
                        ],
                        'shipping' => [
                        'price'    => 0.01,
                        'quantity' => 1,
                        'sum'      => 0.01,
                        ],
                ],
        ];


        $actualData[parent::TEST_CASE_NAME_11] = [
            'sum'            => 32127.55,
            'origGrandTotal' => 32130.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 17298.1,
                            'quantity' => 1,
                            'sum'      => 17298.1,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 25.09,
                            'quantity' => 500,
                            'sum'      => 12545,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 865.33,
                            'quantity' => 1,
                            'sum'      => 865.33,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 354.78,
                            'quantity' => 4,
                            'sum'      => 1419.12,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 2.46,
                            'quantity' => 1,
                            'sum'      => 2.46,
                            'tax'      => '',
                        ],
                ],
        ];


        $actualData[parent::TEST_CASE_NAME_12] = [
            'sum'            => 13188.13,
            'origGrandTotal' => 13189.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.73,
                            'quantity' => 1,
                            'sum'      => 5793.73,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 40,
                            'sum'      => 1044,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 40,
                            'sum'      => 840.8,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 50,
                            'sum'      => 1051,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 10,
                            'sum'      => 261,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 50,
                            'sum'      => 1305,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 23.92,
                            'quantity' => 10,
                            'sum'      => 239.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 20,
                            'sum'      => 522,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 28.27,
                            'quantity' => 20,
                            'sum'      => 565.4,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1.86,
                            'quantity' => 1,
                            'sum'      => 1.86,
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
            'sum'            => 13188.14,
            'origGrandTotal' => 13190.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.74,
                            'quantity' => 1,
                            'sum'      => 5793.74,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 40,
                            'sum'      => 1044,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 40,
                            'sum'      => 840.8,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 50,
                            'sum'      => 1051,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 10,
                            'sum'      => 261,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 50,
                            'sum'      => 1305,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 23.92,
                            'quantity' => 10,
                            'sum'      => 239.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 20,
                            'sum'      => 522,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 28.27,
                            'quantity' => 20,
                            'sum'      => 565.4,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1.87,
                            'quantity' => 1,
                            'sum'      => 1.87,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_15] = [
            'sum'            => 13188,
            'origGrandTotal' => 13189.69,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.6,
                            'quantity' => 1,
                            'sum'      => 5793.6,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 40,
                            'sum'      => 1044,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 40,
                            'sum'      => 840.8,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 21.02,
                            'quantity' => 50,
                            'sum'      => 1051,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 30,
                            'sum'      => 783,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 10,
                            'sum'      => 261,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 50,
                            'sum'      => 1305,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 23.92,
                            'quantity' => 10,
                            'sum'      => 239.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 26.1,
                            'quantity' => 20,
                            'sum'      => 522,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 28.27,
                            'quantity' => 20,
                            'sum'      => 565.4,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 1.69,
                            'quantity' => 1,
                            'sum'      => 1.69,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_16] = [
            'sum'            => 5188,
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
                            'price'    => 18.31,
                            'quantity' => 30,
                            'sum'      => 549.3,
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
                            'price'    => 14.75,
                            'quantity' => 50,
                            'sum'      => 737.5,
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
                            'price'    => 18.31,
                            'quantity' => 10,
                            'sum'      => 183.1,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.31,
                            'quantity' => 50,
                            'sum'      => 915.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 16.79,
                            'quantity' => 10,
                            'sum'      => 167.9,
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
                            'price'    => 19.84,
                            'quantity' => 20,
                            'sum'      => 396.8,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 2.01,
                            'quantity' => 1,
                            'sum'      => 2.01,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_17] = [
            'sum'            => 7989.54,
            'origGrandTotal' => 7989.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 30.75,
                            'quantity' => 100,
                            'sum'      => 3075,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 4914.54,
                            'quantity' => 1,
                            'sum'      => 4914.54,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'price'    => 0.45,
                            'quantity' => 1,
                            'sum'      => 0.45,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_18] = [
            'sum'            => 4297.32,
            'origGrandTotal' => 4297.34,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 531.58,
                            'name'     => 'nMmvlOAz',
                            'quantity' => 1,
                            'sum'      => 531.58,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 0,
                            'name'     => 'wbXGe16K',
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 790.59,
                            'name'     => 'QGqFfLTH',
                            'quantity' => 1,
                            'sum'      => 790.59,
                            'tax'      => 'vat18',
                        ],
                    3          =>
                        [
                            'price'    => 2612.35,
                            'name'     => 'Rwap6BTl',
                            'quantity' => 1,
                            'sum'      => 2612.35,
                            'tax'      => 'vat18',
                        ],
                    4          =>
                        [
                            'price'    => 362.8,
                            'name'     => 'nq7jH66c',
                            'quantity' => 1,
                            'sum'      => 362.8,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'name'     => '',
                            'price'    => 0.02,
                            'quantity' => 1,
                            'sum'      => 0.02,
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
                            'name'     => 'vhHU0E84',
                            'quantity' => 5,
                            'sum'      => 5722.9,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 2801.86,
                            'name'     => 'i9Mt0o1G',
                            'quantity' => 3,
                            'sum'      => 8405.58,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 543.17,
                            'name'     => '9eQwBxLI',
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

        return $actualData;
    }
}
