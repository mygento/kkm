<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountSpreadAndSplitTest extends DiscountGeneralTestCase
{

    protected function setUp()
    {
        $this->discountHelp = new Mygento_Kkm_Helper_Discount();
        $this->discountHelp->setSpreadDiscOnAllUnits(true);
        $this->discountHelp->setIsSplitItemsAllowed(true);
    }

    /**
     * Attention! Order of items in array is important!
     * @dataProvider dataProviderOrdersForCheckCalculation
     */
    public function testCalculation($order, $expectedArray)
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
        $actualData[parent::TEST_CASE_NAME_1] = [
            'sum'            => 12069.30,
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
                        'price'    => 351.8,
                        'quantity' => 1,
                        'sum'      => 351.8,
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

        $actualData[parent::TEST_CASE_NAME_2] = [
            'sum'            => 5086.19,
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
                        'quantity' => 1,
                        'sum'      => 23.63,
                        ],
                        '153_2'    => [
                        'price'    => 23.64,
                        'quantity' => 2,
                        'sum'      => 47.28,
                        ],
                        'shipping' => [
                        'price'    => 4287.00,
                        'quantity' => 1,
                        'sum'      => 4287.00,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_3] = [
            'sum'            => 5086.19,
            'origGrandTotal' => 5106.19,
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
                        'quantity' => 1,
                        'sum'      => 23.63,
                        ],
                        '153_2'    =>
                        [
                            'price'    => 23.64,
                            'quantity' => 2,
                            'sum'      => 47.28,
                        ],
                        'shipping' => [
                        'price'    => 20.00,
                        'quantity' => 1,
                        'sum'      => 20.00,
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
            'sum'            => 202.1,
            'origGrandTotal' => 202.1,
            'items'          =>
                [
                    '152_1'        =>
                        [
                            'price'    => 36.41,
                            'quantity' => 2,
                            'sum'      => 72.82,
                        ],
                        '152_2'        =>
                        [
                            'price'    => 36.42,
                            'quantity' => 1,
                            'sum'      => 36.42,
                        ],
                        '153_1'        =>
                        [
                            'price'    => 23.21,
                            'quantity' => 2,
                            'sum'      => 46.42,
                        ],
                        '153_2'        =>
                        [
                            'price'    => 23.22,
                            'quantity' => 2,
                            'sum'      => 46.44,
                        ],
                        'shipping' => [
                        'price'    => 0.0,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_6] = [
            'sum'            => 702.1,
            'origGrandTotal' => 702.1,
            'items'          =>
                [
                    '152_1'        =>
                        [
                            'price'    => 38.89,
                            'quantity' => 1,
                            'sum'      => 38.89,
                        ],
                        '152_2'        =>
                        [
                            'price'    => 38.9,
                            'quantity' => 2,
                            'sum'      => 77.8,
                        ],
                        '153_1'        =>
                        [
                            'price'    => 24.79,
                            'quantity' => 1,
                            'sum'      => 24.79,
                        ],
                        '153_2'        =>
                        [
                            'price'    => 24.8,
                            'quantity' => 3,
                            'sum'      => 74.40,
                        ],
                        '154_1'        =>
                        [
                            'price'    => 97.24,
                            'quantity' => 3,
                            'sum'      => 291.72,
                        ],
                        '154_2'        =>
                        [
                            'price'    => 97.25,
                            'quantity' => 2,
                            'sum'      => 194.5,
                        ],
                        'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_7] = [
            'sum'            => 11691.0,
            'origGrandTotal' => 11691.0,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 11673.03,
                            'quantity' => 1,
                            'sum'      => 11673.03,
                        ],
                        153        =>
                        [
                            'price'    => 17.97,
                            'quantity' => 1,
                            'sum'      => 17.97,
                        ],
                        'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_8] = [
            'sum'            => 11611.0,
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
                            'price'    => 17.85,
                            'quantity' => 1,
                            'sum'      => 17.85,
                        ],
                        'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
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
                            'name'     => 'qGsDb5jK',
                            'quantity' => 1,
                            'sum'      => 12890,
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

        $actualData[parent::TEST_CASE_NAME_10] = [
            'sum'            => 12909.99,
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

        $actualData[parent::TEST_CASE_NAME_11] = [
            'sum'            => 32130.01,
            'origGrandTotal' => 32130.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 17298.11,
                            'name'     => 'tVPQqM6P',
                            'quantity' => 1,
                            'sum'      => 17298.11,
                            'tax'      => 'vat18',
                        ],
                        '100523_1' =>
                        [
                            'price'    => 25.09,
                            'name'     => 'dawEIFWS',
                            'quantity' => 260,
                            'sum'      => 6523.4,
                            'tax'      => 'vat18',
                        ],
                        '100523_2' =>
                        [
                            'price'    => 25.1,
                            'name'     => 'dawEIFWS',
                            'quantity' => 240,
                            'sum'      => 6024,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 865.35,
                            'name'     => 'GLr5lhBO',
                            'quantity' => 1,
                            'sum'      => 865.35,
                            'tax'      => 'vat18',
                        ],
                        '100525_1' =>
                        [
                            'price'    => 354.78,
                            'name'     => 'DgCaf1zm',
                            'quantity' => 1,
                            'sum'      => 354.78,
                            'tax'      => 'vat18',
                        ],
                        '100525_2' =>
                        [
                            'price'    => 354.79,
                            'name'     => 'DgCaf1zm',
                            'quantity' => 3,
                            'sum'      => 1064.37,
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

        $actualData[parent::TEST_CASE_NAME_12] = [
            'sum'            => 13189.99,
            'origGrandTotal' => 13189.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.74,
                            'name'     => 'KJmABDf6',
                            'quantity' => 1,
                            'sum'      => 5793.74,
                            'tax'      => 'vat18',
                        ],
                        '100527_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '6nFZC8V8',
                            'quantity' => 22,
                            'sum'      => 574.2,
                            'tax'      => 'vat18',
                        ],
                        '100527_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '6nFZC8V8',
                            'quantity' => 18,
                            'sum'      => 469.98,
                            'tax'      => 'vat18',
                        ],
                        '100528_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '0YMYJeK7',
                            'quantity' => 17,
                            'sum'      => 443.7,
                            'tax'      => 'vat18',
                        ],
                        '100528_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '0YMYJeK7',
                            'quantity' => 13,
                            'sum'      => 339.43,
                            'tax'      => 'vat18',
                        ],
                        '100529_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => '1jPzAF9j',
                            'quantity' => 6,
                            'sum'      => 126.12,
                            'tax'      => 'vat18',
                        ],
                        '100529_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => '1jPzAF9j',
                            'quantity' => 34,
                            'sum'      => 715.02,
                            'tax'      => 'vat18',
                        ],
                        '100530_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => 'gD8Tjaok',
                            'quantity' => 7,
                            'sum'      => 147.14,
                            'tax'      => 'vat18',
                        ],
                        '100530_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => 'gD8Tjaok',
                            'quantity' => 43,
                            'sum'      => 904.29,
                            'tax'      => 'vat18',
                        ],
                        '100531_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'rLfMKyoC',
                            'quantity' => 17,
                            'sum'      => 443.7,
                            'tax'      => 'vat18',
                        ],
                        '100531_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'rLfMKyoC',
                            'quantity' => 13,
                            'sum'      => 339.43,
                            'tax'      => 'vat18',
                        ],
                        '100532_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'L4IqEshr',
                            'quantity' => 6,
                            'sum'      => 156.6,
                            'tax'      => 'vat18',
                        ],
                        '100532_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'L4IqEshr',
                            'quantity' => 4,
                            'sum'      => 104.44,
                            'tax'      => 'vat18',
                        ],
                        '100533_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'l4p1n791',
                            'quantity' => 28,
                            'sum'      => 730.8,
                            'tax'      => 'vat18',
                        ],
                        '100533_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'l4p1n791',
                            'quantity' => 22,
                            'sum'      => 574.42,
                            'tax'      => 'vat18',
                        ],
                        '100534_1' =>
                        [
                            'price'    => 23.92,
                            'name'     => 'hfzoDuFf',
                            'quantity' => 1,
                            'sum'      => 23.92,
                            'tax'      => 'vat18',
                        ],
                        '100534_2' =>
                        [
                            'price'    => 23.93,
                            'name'     => 'hfzoDuFf',
                            'quantity' => 9,
                            'sum'      => 215.37,
                            'tax'      => 'vat18',
                        ],
                        '100535_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '8PWR3Pop',
                            'quantity' => 11,
                            'sum'      => 287.1,
                            'tax'      => 'vat18',
                        ],
                        '100535_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '8PWR3Pop',
                            'quantity' => 9,
                            'sum'      => 234.99,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 28.28,
                            'name'     => 'kOto9Vfv',
                            'quantity' => 20,
                            'sum'      => 565.6,
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

        $actualData[parent::TEST_CASE_NAME_13] = [
            'sum'            => 5199.99,
            'origGrandTotal' => 5199.99,
            'items'          =>
                [
                    '100537_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'LAgOo1AI',
                            'quantity' => 28,
                            'sum'      => 513.8,
                            'tax'      => 'vat18',
                        ],
                        '100537_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'LAgOo1AI',
                            'quantity' => 12,
                            'sum'      => 220.32,
                            'tax'      => 'vat18',
                        ],
                        '100538_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'NqIQuqd7',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100538_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'NqIQuqd7',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100539_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'xzZITJyE',
                            'quantity' => 23,
                            'sum'      => 339.94,
                            'tax'      => 'vat18',
                        ],
                        '100539_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'xzZITJyE',
                            'quantity' => 17,
                            'sum'      => 251.43,
                            'tax'      => 'vat18',
                        ],
                        '100540_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'wKiM556l',
                            'quantity' => 29,
                            'sum'      => 428.62,
                            'tax'      => 'vat18',
                        ],
                        '100540_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'wKiM556l',
                            'quantity' => 21,
                            'sum'      => 310.59,
                            'tax'      => 'vat18',
                        ],
                        '100541_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'n1MkSUN8',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100541_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'n1MkSUN8',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100542_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'yG0B7EKY',
                            'quantity' => 7,
                            'sum'      => 128.45,
                            'tax'      => 'vat18',
                        ],
                        '100542_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'yG0B7EKY',
                            'quantity' => 3,
                            'sum'      => 55.08,
                            'tax'      => 'vat18',
                        ],
                        '100543_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'P2aMixwX',
                            'quantity' => 36,
                            'sum'      => 660.6,
                            'tax'      => 'vat18',
                        ],
                        '100543_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'P2aMixwX',
                            'quantity' => 14,
                            'sum'      => 257.04,
                            'tax'      => 'vat18',
                        ],
                        '100544_1' =>
                        [
                            'price'    => 16.82,
                            'name'     => 'RdIoXoWg',
                            'quantity' => 7,
                            'sum'      => 117.74,
                            'tax'      => 'vat18',
                        ],
                        '100544_2' =>
                        [
                            'price'    => 16.83,
                            'name'     => 'RdIoXoWg',
                            'quantity' => 3,
                            'sum'      => 50.49,
                            'tax'      => 'vat18',
                        ],
                        '100545_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'i0mX1Des',
                            'quantity' => 14,
                            'sum'      => 256.9,
                            'tax'      => 'vat18',
                        ],
                        '100545_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'i0mX1Des',
                            'quantity' => 6,
                            'sum'      => 110.16,
                            'tax'      => 'vat18',
                        ],
                        '100546_1' =>
                        [
                            'price'    => 19.88,
                            'name'     => 'u40iqwaA',
                            'quantity' => 15,
                            'sum'      => 298.2,
                            'tax'      => 'vat18',
                        ],
                        '100546_2' =>
                        [
                            'price'    => 19.89,
                            'name'     => 'u40iqwaA',
                            'quantity' => 5,
                            'sum'      => 99.45,
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

        $actualData[parent::TEST_CASE_NAME_14] = [
            'sum'            => 13190.01,
            'origGrandTotal' => 13190.01,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.75,
                            'name'     => '84MEoc1Y',
                            'quantity' => 1,
                            'sum'      => 5793.75,
                            'tax'      => 'vat18',
                        ],
                        '100548_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'VDAYw0zS',
                            'quantity' => 21,
                            'sum'      => 548.1,
                            'tax'      => 'vat18',
                        ],
                        '100548_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'VDAYw0zS',
                            'quantity' => 19,
                            'sum'      => 496.09,
                            'tax'      => 'vat18',
                        ],
                        '100549_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '4UUBllTv',
                            'quantity' => 17,
                            'sum'      => 443.7,
                            'tax'      => 'vat18',
                        ],
                        '100549_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '4UUBllTv',
                            'quantity' => 13,
                            'sum'      => 339.43,
                            'tax'      => 'vat18',
                        ],
                        '100550_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => 'lVP8ceEm',
                            'quantity' => 6,
                            'sum'      => 126.12,
                            'tax'      => 'vat18',
                        ],
                        '100550_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => 'lVP8ceEm',
                            'quantity' => 34,
                            'sum'      => 715.02,
                            'tax'      => 'vat18',
                        ],
                        '100551_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => 'Lx52oORB',
                            'quantity' => 7,
                            'sum'      => 147.14,
                            'tax'      => 'vat18',
                        ],
                        '100551_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => 'Lx52oORB',
                            'quantity' => 43,
                            'sum'      => 904.29,
                            'tax'      => 'vat18',
                        ],
                        '100552_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '9APZJHJX',
                            'quantity' => 17,
                            'sum'      => 443.7,
                            'tax'      => 'vat18',
                        ],
                        '100552_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '9APZJHJX',
                            'quantity' => 13,
                            'sum'      => 339.43,
                            'tax'      => 'vat18',
                        ],
                        '100553_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '1jSEl731',
                            'quantity' => 6,
                            'sum'      => 156.6,
                            'tax'      => 'vat18',
                        ],
                        '100553_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '1jSEl731',
                            'quantity' => 4,
                            'sum'      => 104.44,
                            'tax'      => 'vat18',
                        ],
                        '100554_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'mU5bfDEV',
                            'quantity' => 28,
                            'sum'      => 730.8,
                            'tax'      => 'vat18',
                        ],
                        '100554_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'mU5bfDEV',
                            'quantity' => 22,
                            'sum'      => 574.42,
                            'tax'      => 'vat18',
                        ],
                        '100555_1' =>
                        [
                            'price'    => 23.92,
                            'name'     => '3seC8xNP',
                            'quantity' => 1,
                            'sum'      => 23.92,
                            'tax'      => 'vat18',
                        ],
                        '100555_2' =>
                        [
                            'price'    => 23.93,
                            'name'     => '3seC8xNP',
                            'quantity' => 9,
                            'sum'      => 215.37,
                            'tax'      => 'vat18',
                        ],
                        '100556_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'Yf5U4J8g',
                            'quantity' => 11,
                            'sum'      => 287.1,
                            'tax'      => 'vat18',
                        ],
                        '100556_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'Yf5U4J8g',
                            'quantity' => 9,
                            'sum'      => 234.99,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 28.28,
                            'name'     => 'cbn8w9ya',
                            'quantity' => 20,
                            'sum'      => 565.6,
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

        $actualData[parent::TEST_CASE_NAME_15] = [
            'sum'            => 13189.69,
            'origGrandTotal' => 13189.69,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 5793.6,
                            'name'     => 'kz5oHmL6',
                            'quantity' => 1,
                            'sum'      => 5793.6,
                            'tax'      => 'vat18',
                        ],
                        '100559_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'cewKmxkL',
                            'quantity' => 25,
                            'sum'      => 652.5,
                            'tax'      => 'vat18',
                        ],
                        '100559_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'cewKmxkL',
                            'quantity' => 15,
                            'sum'      => 391.65,
                            'tax'      => 'vat18',
                        ],
                        '100560_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'Mkr1KjKu',
                            'quantity' => 18,
                            'sum'      => 469.8,
                            'tax'      => 'vat18',
                        ],
                        '100560_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'Mkr1KjKu',
                            'quantity' => 12,
                            'sum'      => 313.32,
                            'tax'      => 'vat18',
                        ],
                        '100561_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => '2lqFDqfm',
                            'quantity' => 8,
                            'sum'      => 168.16,
                            'tax'      => 'vat18',
                        ],
                        '100561_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => '2lqFDqfm',
                            'quantity' => 32,
                            'sum'      => 672.96,
                            'tax'      => 'vat18',
                        ],
                        '100562_1' =>
                        [
                            'price'    => 21.02,
                            'name'     => '91IG7jzc',
                            'quantity' => 10,
                            'sum'      => 210.2,
                            'tax'      => 'vat18',
                        ],
                        '100562_2' =>
                        [
                            'price'    => 21.03,
                            'name'     => '91IG7jzc',
                            'quantity' => 40,
                            'sum'      => 841.2,
                            'tax'      => 'vat18',
                        ],
                        '100563_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'ICYKymBz',
                            'quantity' => 18,
                            'sum'      => 469.8,
                            'tax'      => 'vat18',
                        ],
                        '100563_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'ICYKymBz',
                            'quantity' => 12,
                            'sum'      => 313.32,
                            'tax'      => 'vat18',
                        ],
                        '100564_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'gjn12tUv',
                            'quantity' => 6,
                            'sum'      => 156.6,
                            'tax'      => 'vat18',
                        ],
                        '100564_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'gjn12tUv',
                            'quantity' => 4,
                            'sum'      => 104.44,
                            'tax'      => 'vat18',
                        ],
                        '100565_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => '25wUYkjA',
                            'quantity' => 31,
                            'sum'      => 809.1,
                            'tax'      => 'vat18',
                        ],
                        '100565_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => '25wUYkjA',
                            'quantity' => 19,
                            'sum'      => 496.09,
                            'tax'      => 'vat18',
                        ],
                        '100566_1' =>
                        [
                            'price'    => 23.92,
                            'name'     => 'iFEcrv17',
                            'quantity' => 1,
                            'sum'      => 23.92,
                            'tax'      => 'vat18',
                        ],
                        '100566_2' =>
                        [
                            'price'    => 23.93,
                            'name'     => 'iFEcrv17',
                            'quantity' => 9,
                            'sum'      => 215.37,
                            'tax'      => 'vat18',
                        ],
                        '100567_1' =>
                        [
                            'price'    => 26.1,
                            'name'     => 'znVtmS3R',
                            'quantity' => 12,
                            'sum'      => 313.2,
                            'tax'      => 'vat18',
                        ],
                        '100567_2' =>
                        [
                            'price'    => 26.11,
                            'name'     => 'znVtmS3R',
                            'quantity' => 8,
                            'sum'      => 208.88,
                            'tax'      => 'vat18',
                        ],
                        '100568_1' =>
                        [
                            'price'    => 28.27,
                            'name'     => 'ItbDrgQm',
                            'quantity' => 2,
                            'sum'      => 56.54,
                            'tax'      => 'vat18',
                        ],
                        '100568_2' =>
                        [
                            'price'    => 28.28,
                            'name'     => 'ItbDrgQm',
                            'quantity' => 18,
                            'sum'      => 509.04,
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

        $actualData[parent::TEST_CASE_NAME_16] = [
            'sum'            => 5190.01,
            'origGrandTotal' => 5190.01,
            'items'          =>
                [
                    '100569_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'v24x4rdR',
                            'quantity' => 9,
                            'sum'      => 164.79,
                            'tax'      => 'vat18',
                        ],
                        '100569_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'v24x4rdR',
                            'quantity' => 31,
                            'sum'      => 567.92,
                            'tax'      => 'vat18',
                        ],
                        '100570_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'fbt8jFkP',
                            'quantity' => 7,
                            'sum'      => 128.17,
                            'tax'      => 'vat18',
                        ],
                        '100570_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'fbt8jFkP',
                            'quantity' => 23,
                            'sum'      => 421.36,
                            'tax'      => 'vat18',
                        ],
                        '100571_1' =>
                        [
                            'price'    => 14.75,
                            'name'     => '8x5ke1tD',
                            'quantity' => 16,
                            'sum'      => 236,
                            'tax'      => 'vat18',
                        ],
                        '100571_2' =>
                        [
                            'price'    => 14.76,
                            'name'     => '8x5ke1tD',
                            'quantity' => 24,
                            'sum'      => 354.24,
                            'tax'      => 'vat18',
                        ],
                        '100572_1' =>
                        [
                            'price'    => 14.75,
                            'name'     => 'abCGLpaj',
                            'quantity' => 20,
                            'sum'      => 295,
                            'tax'      => 'vat18',
                        ],
                        '100572_2' =>
                        [
                            'price'    => 14.76,
                            'name'     => 'abCGLpaj',
                            'quantity' => 30,
                            'sum'      => 442.8,
                            'tax'      => 'vat18',
                        ],
                        '100573_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'X7sQC7ys',
                            'quantity' => 7,
                            'sum'      => 128.17,
                            'tax'      => 'vat18',
                        ],
                        '100573_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'X7sQC7ys',
                            'quantity' => 23,
                            'sum'      => 421.36,
                            'tax'      => 'vat18',
                        ],
                        '100574_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'JIiAUxQW',
                            'quantity' => 2,
                            'sum'      => 36.62,
                            'tax'      => 'vat18',
                        ],
                        '100574_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'JIiAUxQW',
                            'quantity' => 8,
                            'sum'      => 146.56,
                            'tax'      => 'vat18',
                        ],
                        '100575_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => '7hqtKHLX',
                            'quantity' => 12,
                            'sum'      => 219.72,
                            'tax'      => 'vat18',
                        ],
                        '100575_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => '7hqtKHLX',
                            'quantity' => 38,
                            'sum'      => 696.16,
                            'tax'      => 'vat18',
                        ],
                        '100576_1' =>
                        [
                            'price'    => 16.79,
                            'name'     => '4IfUtXvp',
                            'quantity' => 9,
                            'sum'      => 151.11,
                            'tax'      => 'vat18',
                        ],
                        '100576_2' =>
                        [
                            'price'    => 16.8,
                            'name'     => '4IfUtXvp',
                            'quantity' => 1,
                            'sum'      => 16.8,
                            'tax'      => 'vat18',
                        ],
                        '100577_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'cEdxQcNd',
                            'quantity' => 5,
                            'sum'      => 91.55,
                            'tax'      => 'vat18',
                        ],
                        '100577_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'cEdxQcNd',
                            'quantity' => 15,
                            'sum'      => 274.8,
                            'tax'      => 'vat18',
                        ],
                        '100578_1' =>
                        [
                            'price'    => 19.84,
                            'name'     => 'q5Lk3U2W',
                            'quantity' => 12,
                            'sum'      => 238.08,
                            'tax'      => 'vat18',
                        ],
                        '100578_2' =>
                        [
                            'price'    => 19.85,
                            'name'     => 'q5Lk3U2W',
                            'quantity' => 8,
                            'sum'      => 158.8,
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

        $actualData[parent::TEST_CASE_NAME_17] = [
            'sum'            => 7989.99,
            'origGrandTotal' => 7989.99,
            'items'          =>
                [
                    '100579_1' =>
                        [
                            'price'    => 30.75,
                            'name'     => '4kiTDqhU',
                            'quantity' => 56,
                            'sum'      => 1722,
                            'tax'      => 'vat18',
                        ],
                        '100579_2' =>
                        [
                            'price'    => 30.76,
                            'name'     => '4kiTDqhU',
                            'quantity' => 44,
                            'sum'      => 1353.44,
                            'tax'      => 'vat18',
                        ],
                        0          =>
                        [
                            'price'    => 4914.55,
                            'name'     => 'PqbrzrVx',
                            'quantity' => 1,
                            'sum'      => 4914.55,
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

        return $actualData;
    }
}
