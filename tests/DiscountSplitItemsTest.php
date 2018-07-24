<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountSplitItemsTest extends DiscountGeneralTestCase
{

    protected function setUp()
    {
        $this->discountHelp = new Mygento_Kkm_Helper_Discount();
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

    /** Test splitting item mechanism
     *
     * @dataProvider dataProviderItemsForSplitting
     */
    public function testProcessedItem($item, $expectedArray)
    {
        $discountHelp = new Mygento_Kkm_Helper_Discount();
        $discountHelp->setIsSplitItemsAllowed(true);

        $split = $discountHelp->getProcessedItem($item);

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

        $actualData[parent::TEST_CASE_NAME_2] = [
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
                        '154_1'        => [
                        'price'    => 10.59,
                        'quantity' => 1,
                        'sum'      => 10.59,
                        ],
                        '153_2'        => [
                        'price'    => 10.6,
                        'quantity' => 2,
                        'sum'      => 21.2,
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
                            'price'    => 33.66,
                            'quantity' => 1,
                            'sum'      => 33.66,
                        ],
                        '152_2'        =>
                        [
                            'price'    => 33.67,
                            'quantity' => 2,
                            'sum'      => 67.34,
                        ],
                        '153_1'        =>
                        [
                            'price'    => 25.27,
                            'quantity' => 2,
                            'sum'      => 50.54,
                        ],
                        '153_2'        =>
                        [
                            'price'    => 25.28,
                            'quantity' => 2,
                            'sum'      => 50.56,
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
                            'price'    => 33.66,
                            'quantity' => 1,
                            'sum'      => 33.66,
                        ],
                        '152_2'        =>
                        [
                            'price'    => 33.67,
                            'quantity' => 2,
                            'sum'      => 67.34,
                        ],
                        '153_1'        =>
                        [
                            'price'    => 25.27,
                            'quantity' => 2,
                            'sum'      => 50.54,
                        ],
                        '153_2'        =>
                        [
                            'price'    => 25.28,
                            'quantity' => 2,
                            'sum'      => 50.56,
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

        $actualData[parent::TEST_CASE_NAME_7] = [
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

        $actualData[parent::TEST_CASE_NAME_8] = [
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

        $actualData[parent::TEST_CASE_NAME_9] = [
            'sum'            => 12890.0,
            'origGrandTotal' => 12890.0,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 12890.0,
                            'quantity' => 1,
                            'sum'      => 12890.0,
                        ],
                        'shipping' => [
                        'price'    => 0.00,
                        'quantity' => 1,
                        'sum'      => 0.00,
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
                            'price'    => 19990,
                            'quantity' => 1,
                            'sum'      => 19990,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 19,
                            'quantity' => 500,
                            'sum'      => 9500,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 1000.01,
                            'quantity' => 1,
                            'sum'      => 1000.01,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 410,
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
            'sum'            => 13189.99,
            'origGrandTotal' => 13189.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 7989.99,
                            'name'     => 'HbcIyFpc',
                            'quantity' => 1,
                            'sum'      => 7989.99,
                            'tax'      => 'vat18',
                        ],
                        '100527_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'iVZQ2iMO',
                            'quantity' => 28,
                            'sum'      => 513.8,
                            'tax'      => 'vat18',
                        ],
                        '100527_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'iVZQ2iMO',
                            'quantity' => 12,
                            'sum'      => 220.32,
                            'tax'      => 'vat18',
                        ],
                        '100528_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'CVUohjpK',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100528_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'CVUohjpK',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100529_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => '3JFWNpUY',
                            'quantity' => 23,
                            'sum'      => 339.94,
                            'tax'      => 'vat18',
                        ],
                        '100529_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => '3JFWNpUY',
                            'quantity' => 17,
                            'sum'      => 251.43,
                            'tax'      => 'vat18',
                        ],
                        '100530_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'eLjly6un',
                            'quantity' => 28,
                            'sum'      => 413.84,
                            'tax'      => 'vat18',
                        ],
                        '100530_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'eLjly6un',
                            'quantity' => 22,
                            'sum'      => 325.38,
                            'tax'      => 'vat18',
                        ],
                        '100531_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'nZ0KslHN',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100531_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'nZ0KslHN',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100532_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'xk0eWAiC',
                            'quantity' => 7,
                            'sum'      => 128.45,
                            'tax'      => 'vat18',
                        ],
                        '100532_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'xk0eWAiC',
                            'quantity' => 3,
                            'sum'      => 55.08,
                            'tax'      => 'vat18',
                        ],
                        '100533_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'QZc84oGJ',
                            'quantity' => 35,
                            'sum'      => 642.25,
                            'tax'      => 'vat18',
                        ],
                        '100533_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'QZc84oGJ',
                            'quantity' => 15,
                            'sum'      => 275.4,
                            'tax'      => 'vat18',
                        ],
                        '100534_1' =>
                        [
                            'price'    => 16.82,
                            'name'     => 'EZ45M8YX',
                            'quantity' => 6,
                            'sum'      => 100.92,
                            'tax'      => 'vat18',
                        ],
                        '100534_2' =>
                        [
                            'price'    => 16.83,
                            'name'     => 'EZ45M8YX',
                            'quantity' => 4,
                            'sum'      => 67.32,
                            'tax'      => 'vat18',
                        ],
                        '100535_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => '1fSGTfUL',
                            'quantity' => 14,
                            'sum'      => 256.9,
                            'tax'      => 'vat18',
                        ],
                        '100535_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => '1fSGTfUL',
                            'quantity' => 6,
                            'sum'      => 110.16,
                            'tax'      => 'vat18',
                        ],
                        '100536_1' =>
                        [
                            'price'    => 19.88,
                            'name'     => 'KK1Iub5Q',
                            'quantity' => 17,
                            'sum'      => 337.96,
                            'tax'      => 'vat18',
                        ],
                        '100536_2' =>
                        [
                            'price'    => 19.89,
                            'name'     => 'KK1Iub5Q',
                            'quantity' => 3,
                            'sum'      => 59.67,
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
                            'name'     => 'QasOwBxx',
                            'quantity' => 29,
                            'sum'      => 532.15,
                            'tax'      => 'vat18',
                        ],
                        '100537_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'QasOwBxx',
                            'quantity' => 11,
                            'sum'      => 201.96,
                            'tax'      => 'vat18',
                        ],
                        '100538_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'i3q2Pqi2',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100538_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'i3q2Pqi2',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100539_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => '4yAnOQ9Q',
                            'quantity' => 23,
                            'sum'      => 339.94,
                            'tax'      => 'vat18',
                        ],
                        '100539_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => '4yAnOQ9Q',
                            'quantity' => 17,
                            'sum'      => 251.43,
                            'tax'      => 'vat18',
                        ],
                        '100540_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'BpzRVt8O',
                            'quantity' => 28,
                            'sum'      => 413.84,
                            'tax'      => 'vat18',
                        ],
                        '100540_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'BpzRVt8O',
                            'quantity' => 22,
                            'sum'      => 325.38,
                            'tax'      => 'vat18',
                        ],
                        '100541_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'bOsve0El',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100541_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'bOsve0El',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100542_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'VD4fh7ow',
                            'quantity' => 7,
                            'sum'      => 128.45,
                            'tax'      => 'vat18',
                        ],
                        '100542_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'VD4fh7ow',
                            'quantity' => 3,
                            'sum'      => 55.08,
                            'tax'      => 'vat18',
                        ],
                        '100543_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => '7rUyXogC',
                            'quantity' => 35,
                            'sum'      => 642.25,
                            'tax'      => 'vat18',
                        ],
                        '100543_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => '7rUyXogC',
                            'quantity' => 15,
                            'sum'      => 275.4,
                            'tax'      => 'vat18',
                        ],
                        '100544_1' =>
                        [
                            'price'    => 16.82,
                            'name'     => '4Vjv1sDw',
                            'quantity' => 6,
                            'sum'      => 100.92,
                            'tax'      => 'vat18',
                        ],
                        '100544_2' =>
                        [
                            'price'    => 16.83,
                            'name'     => '4Vjv1sDw',
                            'quantity' => 4,
                            'sum'      => 67.32,
                            'tax'      => 'vat18',
                        ],
                        '100545_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'I1v7ozqi',
                            'quantity' => 14,
                            'sum'      => 256.9,
                            'tax'      => 'vat18',
                        ],
                        '100545_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'I1v7ozqi',
                            'quantity' => 6,
                            'sum'      => 110.16,
                            'tax'      => 'vat18',
                        ],
                        '100546_1' =>
                        [
                            'price'    => 19.88,
                            'name'     => 'vPZr7cV2',
                            'quantity' => 17,
                            'sum'      => 337.96,
                            'tax'      => 'vat18',
                        ],
                        '100546_2' =>
                        [
                            'price'    => 19.89,
                            'name'     => 'vPZr7cV2',
                            'quantity' => 3,
                            'sum'      => 59.67,
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
                            'price'    => 7990.01,
                            'name'     => 'BPIXObQh',
                            'quantity' => 1,
                            'sum'      => 7990.01,
                            'tax'      => 'vat18',
                        ],
                        '100548_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'kXtzFQk9',
                            'quantity' => 28,
                            'sum'      => 513.8,
                            'tax'      => 'vat18',
                        ],
                        '100548_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'kXtzFQk9',
                            'quantity' => 12,
                            'sum'      => 220.32,
                            'tax'      => 'vat18',
                        ],
                        '100549_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'VjBZWKqC',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100549_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'VjBZWKqC',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100550_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'yOhCey5i',
                            'quantity' => 23,
                            'sum'      => 339.94,
                            'tax'      => 'vat18',
                        ],
                        '100550_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'yOhCey5i',
                            'quantity' => 17,
                            'sum'      => 251.43,
                            'tax'      => 'vat18',
                        ],
                        '100551_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'x744Y2VU',
                            'quantity' => 28,
                            'sum'      => 413.84,
                            'tax'      => 'vat18',
                        ],
                        '100551_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'x744Y2VU',
                            'quantity' => 22,
                            'sum'      => 325.38,
                            'tax'      => 'vat18',
                        ],
                        '100552_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'LNgNKsqq',
                            'quantity' => 21,
                            'sum'      => 385.35,
                            'tax'      => 'vat18',
                        ],
                        '100552_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'LNgNKsqq',
                            'quantity' => 9,
                            'sum'      => 165.24,
                            'tax'      => 'vat18',
                        ],
                        '100553_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => '4vrYuAmx',
                            'quantity' => 7,
                            'sum'      => 128.45,
                            'tax'      => 'vat18',
                        ],
                        '100553_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => '4vrYuAmx',
                            'quantity' => 3,
                            'sum'      => 55.08,
                            'tax'      => 'vat18',
                        ],
                        '100554_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => '5KCqwVCK',
                            'quantity' => 35,
                            'sum'      => 642.25,
                            'tax'      => 'vat18',
                        ],
                        '100554_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => '5KCqwVCK',
                            'quantity' => 15,
                            'sum'      => 275.4,
                            'tax'      => 'vat18',
                        ],
                        '100555_1' =>
                        [
                            'price'    => 16.82,
                            'name'     => 'SiJ3Zm9y',
                            'quantity' => 6,
                            'sum'      => 100.92,
                            'tax'      => 'vat18',
                        ],
                        '100555_2' =>
                        [
                            'price'    => 16.83,
                            'name'     => 'SiJ3Zm9y',
                            'quantity' => 4,
                            'sum'      => 67.32,
                            'tax'      => 'vat18',
                        ],
                        '100556_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => '2IqCgAK6',
                            'quantity' => 14,
                            'sum'      => 256.9,
                            'tax'      => 'vat18',
                        ],
                        '100556_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => '2IqCgAK6',
                            'quantity' => 6,
                            'sum'      => 110.16,
                            'tax'      => 'vat18',
                        ],
                        '100557_1' =>
                        [
                            'price'    => 19.88,
                            'name'     => 'UDzfCEuJ',
                            'quantity' => 17,
                            'sum'      => 337.96,
                            'tax'      => 'vat18',
                        ],
                        '100557_2' =>
                        [
                            'price'    => 19.89,
                            'name'     => 'UDzfCEuJ',
                            'quantity' => 3,
                            'sum'      => 59.67,
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
                            'price'    => 7989.96,
                            'name'     => 'WK6xqA9P',
                            'quantity' => 1,
                            'sum'      => 7989.96,
                            'tax'      => 'vat18',
                        ],
                        '100559_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'TGKkaZv4',
                            'quantity' => 32,
                            'sum'      => 587.2,
                            'tax'      => 'vat18',
                        ],
                        '100559_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'TGKkaZv4',
                            'quantity' => 8,
                            'sum'      => 146.88,
                            'tax'      => 'vat18',
                        ],
                        '100560_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'EixOXbqy',
                            'quantity' => 25,
                            'sum'      => 458.75,
                            'tax'      => 'vat18',
                        ],
                        '100560_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'EixOXbqy',
                            'quantity' => 5,
                            'sum'      => 91.8,
                            'tax'      => 'vat18',
                        ],
                        '100561_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'kCJIu3aM',
                            'quantity' => 27,
                            'sum'      => 399.06,
                            'tax'      => 'vat18',
                        ],
                        '100561_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'kCJIu3aM',
                            'quantity' => 13,
                            'sum'      => 192.27,
                            'tax'      => 'vat18',
                        ],
                        '100562_1' =>
                        [
                            'price'    => 14.78,
                            'name'     => 'MdGYRsVO',
                            'quantity' => 31,
                            'sum'      => 458.18,
                            'tax'      => 'vat18',
                        ],
                        '100562_2' =>
                        [
                            'price'    => 14.79,
                            'name'     => 'MdGYRsVO',
                            'quantity' => 19,
                            'sum'      => 281.01,
                            'tax'      => 'vat18',
                        ],
                        '100563_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'ttQ8ylbR',
                            'quantity' => 23,
                            'sum'      => 422.05,
                            'tax'      => 'vat18',
                        ],
                        '100563_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'ttQ8ylbR',
                            'quantity' => 7,
                            'sum'      => 128.52,
                            'tax'      => 'vat18',
                        ],
                        '100564_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'wWrAvSS9',
                            'quantity' => 9,
                            'sum'      => 165.15,
                            'tax'      => 'vat18',
                        ],
                        '100564_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'wWrAvSS9',
                            'quantity' => 1,
                            'sum'      => 18.36,
                            'tax'      => 'vat18',
                        ],
                        '100565_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'v415Myym',
                            'quantity' => 37,
                            'sum'      => 678.95,
                            'tax'      => 'vat18',
                        ],
                        '100565_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'v415Myym',
                            'quantity' => 13,
                            'sum'      => 238.68,
                            'tax'      => 'vat18',
                        ],
                        '100566_1' =>
                        [
                            'price'    => 16.82,
                            'name'     => '2KkA1lAQ',
                            'quantity' => 8,
                            'sum'      => 134.56,
                            'tax'      => 'vat18',
                        ],
                        '100566_2' =>
                        [
                            'price'    => 16.83,
                            'name'     => '2KkA1lAQ',
                            'quantity' => 2,
                            'sum'      => 33.66,
                            'tax'      => 'vat18',
                        ],
                        '100567_1' =>
                        [
                            'price'    => 18.35,
                            'name'     => 'Mbg5nGZU',
                            'quantity' => 16,
                            'sum'      => 293.6,
                            'tax'      => 'vat18',
                        ],
                        '100567_2' =>
                        [
                            'price'    => 18.36,
                            'name'     => 'Mbg5nGZU',
                            'quantity' => 4,
                            'sum'      => 73.44,
                            'tax'      => 'vat18',
                        ],
                        '100568_1' =>
                        [
                            'price'    => 19.88,
                            'name'     => 'janvE9Ay',
                            'quantity' => 19,
                            'sum'      => 377.72,
                            'tax'      => 'vat18',
                        ],
                        '100568_2' =>
                        [
                            'price'    => 19.89,
                            'name'     => 'janvE9Ay',
                            'quantity' => 1,
                            'sum'      => 19.89,
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
                            'name'     => 'aQQcXkLB',
                            'quantity' => 20,
                            'sum'      => 366.2,
                            'tax'      => 'vat18',
                        ],
                        '100569_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'aQQcXkLB',
                            'quantity' => 20,
                            'sum'      => 366.4,
                            'tax'      => 'vat18',
                        ],
                        '100570_1' =>
                        [
                            'price'    => 18.3,
                            'name'     => 'pP4cXE4c',
                            'quantity' => 9,
                            'sum'      => 164.7,
                            'tax'      => 'vat18',
                        ],
                        '100570_2' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'pP4cXE4c',
                            'quantity' => 21,
                            'sum'      => 384.51,
                            'tax'      => 'vat18',
                        ],
                        '100571_1' =>
                        [
                            'price'    => 14.75,
                            'name'     => 'NeU6lQRX',
                            'quantity' => 27,
                            'sum'      => 398.25,
                            'tax'      => 'vat18',
                        ],
                        '100571_2' =>
                        [
                            'price'    => 14.76,
                            'name'     => 'NeU6lQRX',
                            'quantity' => 13,
                            'sum'      => 191.88,
                            'tax'      => 'vat18',
                        ],
                        '100572_1' =>
                        [
                            'price'    => 14.76,
                            'name'     => 'aHX2WaxX',
                            'quantity' => 39,
                            'sum'      => 575.64,
                            'tax'      => 'vat18',
                        ],
                        '100572_2' =>
                        [
                            'price'    => 14.77,
                            'name'     => 'aHX2WaxX',
                            'quantity' => 11,
                            'sum'      => 162.47,
                            'tax'      => 'vat18',
                        ],
                        '100573_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'fLjhVF9j',
                            'quantity' => 2,
                            'sum'      => 36.62,
                            'tax'      => 'vat18',
                        ],
                        '100573_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'fLjhVF9j',
                            'quantity' => 28,
                            'sum'      => 512.96,
                            'tax'      => 'vat18',
                        ],
                        '100574_1' =>
                        [
                            'price'    => 18.26,
                            'name'     => 'xuu28xie',
                            'quantity' => 8,
                            'sum'      => 146.08,
                            'tax'      => 'vat18',
                        ],
                        '100574_2' =>
                        [
                            'price'    => 18.27,
                            'name'     => 'xuu28xie',
                            'quantity' => 2,
                            'sum'      => 36.54,
                            'tax'      => 'vat18',
                        ],
                        '100575_1' =>
                        [
                            'price'    => 18.33,
                            'name'     => 'XjE4SYr8',
                            'quantity' => 16,
                            'sum'      => 293.28,
                            'tax'      => 'vat18',
                        ],
                        '100575_2' =>
                        [
                            'price'    => 18.34,
                            'name'     => 'XjE4SYr8',
                            'quantity' => 34,
                            'sum'      => 623.56,
                            'tax'      => 'vat18',
                        ],
                        '100576_1' =>
                        [
                            'price'    => 16.74,
                            'name'     => 'ZW6A11yF',
                            'quantity' => 1,
                            'sum'      => 16.74,
                            'tax'      => 'vat18',
                        ],
                        '100576_2' =>
                        [
                            'price'    => 16.75,
                            'name'     => 'ZW6A11yF',
                            'quantity' => 9,
                            'sum'      => 150.75,
                            'tax'      => 'vat18',
                        ],
                        '100577_1' =>
                        [
                            'price'    => 18.31,
                            'name'     => 'UOn0RlkO',
                            'quantity' => 1,
                            'sum'      => 18.31,
                            'tax'      => 'vat18',
                        ],
                        '100577_2' =>
                        [
                            'price'    => 18.32,
                            'name'     => 'UOn0RlkO',
                            'quantity' => 19,
                            'sum'      => 348.08,
                            'tax'      => 'vat18',
                        ],
                        '100578_1' =>
                        [
                            'price'    => 19.85,
                            'name'     => 'ZFV54mXu',
                            'quantity' => 16,
                            'sum'      => 317.6,
                            'tax'      => 'vat18',
                        ],
                        '100578_2' =>
                        [
                            'price'    => 19.86,
                            'name'     => 'ZFV54mXu',
                            'quantity' => 4,
                            'sum'      => 79.44,
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
                    0          =>
                        [
                            'price'    => 0,
                            'name'     => 'BNjkAE3U',
                            'quantity' => 100,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 7989.99,
                            'name'     => 'hUSwdaHQ',
                            'quantity' => 1,
                            'sum'      => 7989.99,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
                            'price'    => 0,
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax' => '',
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
                            'price'    => 531.66,
                            'name'     => 'YXeRYBXo',
                            'quantity' => 1,
                            'sum'      => 531.66,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 0,
                            'name'     => '9Bxbstfm',
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 790.62,
                            'name'     => '1cIi7VzH',
                            'quantity' => 1,
                            'sum'      => 790.62,
                            'tax'      => 'vat18',
                        ],
                    3          =>
                        [
                            'price'    => 2612.25,
                            'name'     => 'WooMopAr',
                            'quantity' => 1,
                            'sum'      => 2612.25,
                            'tax'      => 'vat18',
                        ],
                    4          =>
                        [
                            'price'    => 362.81,
                            'name'     => 'yhDgr1ag',
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

        return $actualData;
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function dataProviderItemsForSplitting()
    {
        $final = [];

        // #1 rowDiff = 2 kop. qty = 3. qtyUpdate = 3
        $item = $this->getItem(0, 0, 0, 3);
        $item->setData(Mygento_Kkm_Helper_Discount::NAME_ROW_DIFF, 2);
        $item->setData(Mygento_Kkm_Helper_Discount::NAME_UNIT_PRICE, 10.59);

        $expected = [
            [
                'price' => 10.59,
                'quantity' => 1,
                'sum' => 10.59,
                'tax' => null,
            ],
            [
                'price' => 10.6,
                'quantity' => 2,
                'sum' => 21.2,
                'tax' => null,
            ],
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
                'price' => 10.01,
                'quantity' => 1,
                'sum' => 10.01,
            ],
            [
                'price' => 10.02,
                'quantity' => 2,
                'sum' => 20.04,
            ],
        ];
        $final['#case 3. 5 копеек распределить по 3 товарам.'] = [$item3, $expected3];

        return $final;
    }
}
