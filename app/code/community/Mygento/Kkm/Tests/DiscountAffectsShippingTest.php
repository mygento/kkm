<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class DiscountAffectsShippingTest extends DiscountGeneralTestCase
{
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
            'sum'            => 5086.17,
            'origGrandTotal' => 9373.19,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 5054.4,
                            'quantity' => 1,
                            'sum'      => 5054.4,
                        ],
                        153        => [
                        'price'    => 10.59,
                        'quantity' => 3,
                        'sum'      => 31.77,
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
                            'quantity' => 1,
                            'sum'      => 5015.28,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 23.63,
                            'quantity' => 3,
                            'sum'      => 70.89,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
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
            'sum'            => 202.06,
            'origGrandTotal' => 202.1,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 33.66,
                            'quantity' => 3,
                            'sum'      => 100.98,
                        ],
                        153        =>
                        [
                            'price'    => 25.27,
                            'quantity' => 4,
                            'sum'      => 101.08,
                        ],
                        'shipping' => [
                        'price'    => 0.04,
                        'quantity' => 1,
                        'sum'      => 0.04,
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_6] = [
            'sum'            => 702.06,
            'origGrandTotal' => 702.1,
            'items'          =>
                [
                    152        =>
                        [
                            'price'    => 33.66,
                            'quantity' => 3,
                            'sum'      => 100.98,
                        ],
                        153        =>
                        [
                            'price'    => 25.27,
                            'quantity' => 4,
                            'sum'      => 101.08,
                        ],
                        154        =>
                        [
                            'price'    => 100,
                            'quantity' => 5,
                            'sum'      => 500,
                        ],
                        'shipping' => [
                        'price'    => 0.04,
                        'quantity' => 1,
                        'sum'      => 0.04,
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
            'sum'            => 11610.99,
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
            'sum'            => 13188.99,
            'origGrandTotal' => 13189.99,
            'items'          =>
                [
                    0          =>
                        [
                            'price'    => 7989.99,
                            'name'     => 'P9MNKYhl',
                            'quantity' => 1,
                            'sum'      => 7989.99,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'name'     => '4ERY91mu',
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'KfeQ4b7b',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'name'     => 't5A2Rxrh',
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'hK09CiPH',
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'tcL2igh1',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'xeFWcmLR',
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'igEqwmIn',
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'name'     => 'eEIuB9Qa',
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'Xbkx2msA',
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'name'     => 'Frg6nmw6',
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
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
                            'name'     => 'MZq6NgUu',
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'rFGIjBMZ',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'LjayXom2',
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'n2uBhwkF',
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'JFe1Ch42',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'lA3FKtTZ',
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'XKrOuj4K',
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 16.82,
                            'name'     => 'Xwd89uKe',
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'Zf8D4wlJ',
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 19.88,
                            'name'     => 'IerjMQ0P',
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
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
                            'name'     => '5pX9HUkK',
                            'quantity' => 1,
                            'sum'      => 7990.01,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'RVauXqDg',
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'eXMVmtNM',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'rKpM7xsp',
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'f3UEyJsu',
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'QRZPcPcn',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'SjPkhLxi',
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'ogIuOKZy',
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'name'     => 'ShFH5gnF',
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'ZdKlAkHt',
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'name'     => 'QOrfYuvX',
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
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
                            'name'     => 'Z7aMJRHe',
                            'quantity' => 1,
                            'sum'      => 7989.96,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'ZGzlY3lG',
                            'quantity' => 40,
                            'sum'      => 734,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 18.35,
                            'name'     => '0QhYCfOx',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'Ddebej9A',
                            'quantity' => 40,
                            'sum'      => 591.2,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 14.78,
                            'name'     => 'x3B4cOC0',
                            'quantity' => 50,
                            'sum'      => 739,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'y8k9XsO1',
                            'quantity' => 30,
                            'sum'      => 550.5,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'jKIoyDr1',
                            'quantity' => 10,
                            'sum'      => 183.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'ScpiBhc7',
                            'quantity' => 50,
                            'sum'      => 917.5,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 16.82,
                            'name'     => 'ZsULLCo2',
                            'quantity' => 10,
                            'sum'      => 168.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 18.35,
                            'name'     => 'C2mcTUaA',
                            'quantity' => 20,
                            'sum'      => 367,
                            'tax'      => 'vat18',
                        ],
                        10         =>
                        [
                            'price'    => 19.88,
                            'name'     => 'XyibNKuf',
                            'quantity' => 20,
                            'sum'      => 397.6,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
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
                            'name'     => 'PYGIbulV',
                            'quantity' => 40,
                            'sum'      => 732.4,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 18.3,
                            'name'     => 'Fhor0s1t',
                            'quantity' => 30,
                            'sum'      => 549,
                            'tax'      => 'vat18',
                        ],
                        2          =>
                        [
                            'price'    => 14.75,
                            'name'     => '2QE7DEhC',
                            'quantity' => 40,
                            'sum'      => 590,
                            'tax'      => 'vat18',
                        ],
                        3          =>
                        [
                            'price'    => 14.76,
                            'name'     => 'cuhpCjPM',
                            'quantity' => 50,
                            'sum'      => 738,
                            'tax'      => 'vat18',
                        ],
                        4          =>
                        [
                            'price'    => 18.31,
                            'name'     => 'jYG8dyAQ',
                            'quantity' => 30,
                            'sum'      => 549.3,
                            'tax'      => 'vat18',
                        ],
                        5          =>
                        [
                            'price'    => 18.26,
                            'name'     => 'Wc9zz8mX',
                            'quantity' => 10,
                            'sum'      => 182.6,
                            'tax'      => 'vat18',
                        ],
                        6          =>
                        [
                            'price'    => 18.33,
                            'name'     => 'ShtjbsVl',
                            'quantity' => 50,
                            'sum'      => 916.5,
                            'tax'      => 'vat18',
                        ],
                        7          =>
                        [
                            'price'    => 16.74,
                            'name'     => 'JJLF9Lim',
                            'quantity' => 10,
                            'sum'      => 167.4,
                            'tax'      => 'vat18',
                        ],
                        8          =>
                        [
                            'price'    => 18.31,
                            'name'     => 'Exyvd5Gy',
                            'quantity' => 20,
                            'sum'      => 366.2,
                            'tax'      => 'vat18',
                        ],
                        9          =>
                        [
                            'price'    => 19.85,
                            'name'     => 'YLrmTbGC',
                            'quantity' => 20,
                            'sum'      => 397,
                            'tax'      => 'vat18',
                        ],
                        'shipping' =>
                        [
                            'name'     => '',
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
                            'name'     => 'NGUlxstu',
                            'quantity' => 100,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                        1          =>
                        [
                            'price'    => 7989.99,
                            'name'     => 'fMLjGnBE',
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
                            'price'    => 531.66,
                            'name'     => 'ef3H6yfu',
                            'quantity' => 1,
                            'sum'      => 531.66,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 0,
                            'name'     => 'gEwo9Ya6',
                            'quantity' => 1,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 790.62,
                            'name'     => 'ST1wrkyq',
                            'quantity' => 1,
                            'sum'      => 790.62,
                            'tax'      => 'vat18',
                        ],
                    3          =>
                        [
                            'price'    => 2612.25,
                            'name'     => 'N1l0lmPT',
                            'quantity' => 1,
                            'sum'      => 2612.25,
                            'tax'      => 'vat18',
                        ],
                    4          =>
                        [
                            'price'    => 362.81,
                            'name'     => 'N1xslSmm',
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
                            'name'     => 'YJmwecYY',
                            'quantity' => 5,
                            'sum'      => 5722.9,
                            'tax'      => 'vat18',
                        ],
                    1          =>
                        [
                            'price'    => 2801.86,
                            'name'     => 'lm8sDuAm',
                            'quantity' => 3,
                            'sum'      => 8405.58,
                            'tax'      => 'vat18',
                        ],
                    2          =>
                        [
                            'price'    => 543.17,
                            'name'     => 'Gs9i60km',
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
            'sum'            => 14161,
            'origGrandTotal' => 14160.01,
            'items'          =>
                [
                    100589     =>
                        [
                            'price'    => 29.01,
                            'name'     => 'nKRZuyly',
                            'quantity' => 100,
                            'sum'      => 2901,
                            'tax'      => 'vat18',
                        ],
                    100590     =>
                        [
                            'price'    => 40,
                            'name'     => 'x9E07wnw',
                            'quantity' => 50,
                            'sum'      => 2000,
                            'tax'      => 'vat18',
                        ],
                    100591     =>
                        [
                            'price'    => 29,
                            'name'     => 'q2zNbzDr',
                            'quantity' => 50,
                            'sum'      => 1450,
                            'tax'      => 'vat18',
                        ],
                    100592     =>
                        [
                            'price'    => 36,
                            'name'     => 'v0x3q6dW',
                            'quantity' => 30,
                            'sum'      => 1080,
                            'tax'      => 'vat18',
                        ],
                    100593     =>
                        [
                            'price'    => 3990,
                            'name'     => '6RtdEZc8',
                            'quantity' => 1,
                            'sum'      => 3990,
                            'tax'      => 'vat18',
                        ],
                    100594     =>
                        [
                            'price'    => 40,
                            'name'     => 'vNeI9KeQ',
                            'quantity' => 50,
                            'sum'      => 2000,
                            'tax'      => 'vat18',
                        ],
                    100595     =>
                        [
                            'price'    => 37,
                            'name'     => 'tGU1Xod7',
                            'quantity' => 20,
                            'sum'      => 740,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'name'     => '',
                            'price'    => -0.99,
                            'quantity' => 1,
                            'sum'      => -0.99,
                            'tax'      => '',
                        ],
                ],
        ];

        $actualData[parent::TEST_CASE_NAME_21] = [
            'sum'            => 17431.3,
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
                            'price'    => 29.01,
                            'quantity' => 30,
                            'sum'      => 870.3,
                            'tax'      => 'vat18',
                        ],
                    100598     =>
                        [
                            'price'    => 37,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100599     =>
                        [
                            'price'    => 37,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100600     =>
                        [
                            'price'    => 37,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100601     =>
                        [
                            'price'    => 37,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100602     =>
                        [
                            'price'    => 37,
                            'quantity' => 40,
                            'sum'      => 1480,
                            'tax'      => 'vat18',
                        ],
                    100603     =>
                        [
                            'price'    => 36,
                            'quantity' => 10,
                            'sum'      => 360,
                            'tax'      => 'vat18',
                        ],
                    100604     =>
                        [
                            'price'    => 29,
                            'quantity' => 60,
                            'sum'      => 1740,
                            'tax'      => 'vat18',
                        ],
                    100605     =>
                        [
                            'price'    => 29,
                            'quantity' => 80,
                            'sum'      => 2320,
                            'tax'      => 'vat18',
                        ],
                    100606     =>
                        [
                            'price'    => 33,
                            'quantity' => 30,
                            'sum'      => 990,
                            'tax'      => 'vat18',
                        ],
                    100607     =>
                        [
                            'price'    => 33,
                            'quantity' => 20,
                            'sum'      => 660,
                            'tax'      => 'vat18',
                        ],
                    100608     =>
                        [
                            'price'    => 33,
                            'quantity' => 10,
                            'sum'      => 330,
                            'tax'      => 'vat18',
                        ],
                    100609     =>
                        [
                            'price'    => 46,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100610     =>
                        [
                            'price'    => 46,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100611     =>
                        [
                            'price'    => 46,
                            'quantity' => 20,
                            'sum'      => 920,
                            'tax'      => 'vat18',
                        ],
                    100612     =>
                        [
                            'price'    => 0,
                            'quantity' => 4,
                            'sum'      => 0,
                            'tax'      => 'vat18',
                        ],
                    'shipping' =>
                        [
                            'price'    => -0.29,
                            'quantity' => 1,
                            'sum'      => -0.29,
                            'tax'      => '',
                        ],
                ],
        ];

        return $actualData;
    }
}
