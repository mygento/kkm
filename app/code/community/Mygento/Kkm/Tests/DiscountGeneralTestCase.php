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
    //consts for getRecalculated() method
    const TEST_CASE_NAME_1  = '#case 1. Скидки только на товары. Все делится нацело. Bug 1 kop. Товары по 1 шт. Два со скидками, а один бесплатный.';
    const TEST_CASE_NAME_2  = '#case 2. Скидка на весь чек и на отдельный товар. Order 145000128 DemoEE';
    const TEST_CASE_NAME_3  = '#case 3. Скидка на каждый товар.';
    const TEST_CASE_NAME_4  = '#case 4. Нет скидок никаких';
    const TEST_CASE_NAME_5  = '#case 5. Скидки только на товары. Не делятся нацело.';
    const TEST_CASE_NAME_6  = '#case 6. Есть позиция, на которую НЕ ДОЛЖНА распространиться скидка.';
    const TEST_CASE_NAME_7  = '#case 7. Bug grandTotal < чем сумма всех позиций. Есть позиция со 100% скидкой.';
    const TEST_CASE_NAME_8  = '#case 8. Reward points в заказе. 1 товар со скидкой, 1 без';
    const TEST_CASE_NAME_9  = '#case 9. Reward points в заказе. В заказе только 1 товар и тот без скидки';
    const TEST_CASE_NAME_10 = '#case 10. Reward points в заказе. На товары нет скидок';
    const TEST_CASE_NAME_11 = '#case 11. (prd nn) order 100374806';
    const TEST_CASE_NAME_12 = '#case 12. invoice NN 100057070. Неверно расчитан grandTotal в Magento';
    const TEST_CASE_NAME_13 = '#case 13. Такой же как и invoice NN 100057070, но без большого продукта. Неверно расчитан grandTotal в Magento';
    const TEST_CASE_NAME_14 = '#case 14. Тест 1 на мелкие rewardPoints (0.01)';
    const TEST_CASE_NAME_15 = '#case 15. Тест 2 на мелкие rewardPoints (0.31)';
    const TEST_CASE_NAME_16 = '#case 16. Тест 1 на мелкие rewardPoints (9.99)';
    const TEST_CASE_NAME_17 = '#case 17. гипотетическая ситуация с ошибкой расчета Мagento -1 коп.';
    const TEST_CASE_NAME_18 = '#case 18. Issue #23 Github';
    const TEST_CASE_NAME_19 = '#case 19. Bug with negative Qty';
    const TEST_CASE_NAME_20 = '#case 20. Bug with negative Price (e.g. invoice 100087282)';

    const CHARS_LOWERS   = 'abcdefghijklmnopqrstuvwxyz';
    const CHARS_UPPERS   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_DIGITS   = '0123456789';
    const CHARS_SPECIALS = '!$*+-.=?@^_|~';

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

    protected function onNotSuccessfulTest(Exception $e)
    {
        //beautify output
        echo "\033[1;31m"; // light red
        echo "\t".$e->getMessage()."\n";
        echo "\033[0m"; //reset color

        throw $e;
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function testCalculation($order, $expectedArray, $key = null)
    {
        //В случае если добавили новый тест и у него еще нет expectedArray - то выводим его с соотв. округлением значений
        if (is_null($expectedArray)) {
            echo "\033[1;32m"; // green
            echo "\n".get_class($this)."\n";
            echo $this->getName().PHP_EOL;
            echo "\033[1;33m"; // yellow
            $storedValue = ini_get('serialize_precision');
            ini_set('serialize_precision', 12);
            var_export($this->discountHelp->getRecalculated($order, 'vat18'));
            ini_set('serialize_precision', $storedValue);
            echo "\033[0m"; // reset color
            exit();
        }
    }

    protected function getNewOrderInstance(
        $subTotalInclTax,
        $grandTotal,
        $shippingInclTax,
        $rewardPoints = 0.00
    ) {
        $order = new Varien_Object();

        $order->setData('subtotal_incl_tax', $subTotalInclTax);
        $order->setData('grand_total', $grandTotal);
        $order->setData('shipping_incl_tax', $shippingInclTax);
        $order->setData(
            'discount_amount',
            $grandTotal + $rewardPoints - $subTotalInclTax - $shippingInclTax
        );

        return $order;
    }

    public function getItem(
        $rowTotalInclTax,
        $priceInclTax,
        $discountAmount,
        $qty = 1,
        $itemId = null
    ) {
        static $id = 100500;
        $id++;

        $item = new Varien_Object();
        $item->setData('id', $itemId ?: $id);
        $item->setData('row_total_incl_tax', $rowTotalInclTax);
        $item->setData('price_incl_tax', $priceInclTax);
        $item->setData('discount_amount', $discountAmount);
        $item->setData('qty', $qty);

        return $item;
    }

    public function addItem($order, $item)
    {
        $items   = (array)$order->getData('all_items');
        $items[] = $item;

        $order->setData('all_items', $items);
    }

    public function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = self::CHARS_LOWERS.self::CHARS_UPPERS.self::CHARS_DIGITS;
        }
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }

        return $str;
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function dataProviderOrdersForCheckCalculation()
    {
        $final = [];

        //Тест кейсы одинаковые для всех вариантов настроек класса Discount
        $orders = self::getOrders();
        //А ожидаемые результаты должны быть в каждом классе свои
        $expected = static::getExpected();

        foreach ($orders as $key => $order) {
            $final[$key] = [$order, $expected[$key], $key];
        }

        return $final;
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function getOrders()
    {
        $final = [];

        //Bug 1 kop. Товары по 1 шт. Два со скидками, а один бесплатный. Цены делятся нацело - пересчет не должен применяться.
        $order = $this->getNewOrderInstance(13380.0000, 12069.3000, 0.0000);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000));
        $this->addItem($order, $this->getItem(390.0000, 390.0000, 11.7000));
        $this->addItem($order, $this->getItem(0.0000, 0.0000, 0.0000));
        $final[self::TEST_CASE_NAME_1] = $order;

        $order = $this->getNewOrderInstance(5125.8600, 9373.1900, 4287.00);
        $this->addItem($order, $this->getItem(5054.4000, 5054.4000, 0.0000));
        $this->addItem($order, $this->getItem(71.4600, 23.8200, 39.6700, 3));
        $final[self::TEST_CASE_NAME_2] = $order;

        //39.67 Discount на весь заказ. Magento размазывает по товарам скидку в заказе.
        $order = $this->getNewOrderInstance(5125.8600, 5106.1900, 20.00);
        $this->addItem($order, $this->getItem(5054.4000, 5054.4000, 39.1200));
        $this->addItem($order, $this->getItem(71.4600, 23.8200, 0.5500, 3));
        $final[self::TEST_CASE_NAME_3] = $order;

        $order = $this->getNewOrderInstance(5000.8600, 5200.8600, 200.00);
        $this->addItem($order, $this->getItem(1000.8200, 500.4100, 0.0000, 2));
        $this->addItem($order, $this->getItem(4000.04, 1000.01, 0.0000, 4));
        $final[self::TEST_CASE_NAME_4] = $order;

        $order = $this->getNewOrderInstance(222, 202.1, 0);
        $this->addItem($order, $this->getItem(120, 40, 19, 3));
        $this->addItem($order, $this->getItem(102, 25.5, 0.9, 4));
        $final[self::TEST_CASE_NAME_5] = $order;

        $order = $this->getNewOrderInstance(722, 702.1, 0);
        $this->addItem($order, $this->getItem(120, 40, 19, 3));
        $this->addItem($order, $this->getItem(102, 25.5, 0.9, 4));
        $this->addItem($order, $this->getItem(500, 100, 0, 5));
        $final[self::TEST_CASE_NAME_6] = $order;

        //Bug GrandTotal заказа меньше, чем сумма всех позиций. На 1 товар скидка 100%
        $order = $this->getNewOrderInstance(13010.0000, 11691.0000, 0);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000, 1));
        $this->addItem($order, $this->getItem(20.0000, 20.0000, 20.0000, 1));
        $final[self::TEST_CASE_NAME_7] = $order;

        //Reward Points included
        $order = $this->getNewOrderInstance(13010.0000, 11611.0000, 0, 100);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 1299.0000, 1));
        $this->addItem($order, $this->getItem(20.0000, 20.0000, 0.0000, 1));
        $final[self::TEST_CASE_NAME_8] = $order;

        //Reward Points included 2
        $order = $this->getNewOrderInstance(12990.0000, 12890.0000, 0, 100);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 0.0000, 1));
        $final[self::TEST_CASE_NAME_9] = $order;

        //Reward Points included 3
        $order = $this->getNewOrderInstance(13010.0000, 12909.9900, 0, 100.01);
        $this->addItem($order, $this->getItem(12990.0000, 12990.0000, 0.0000, 1));
        $this->addItem($order, $this->getItem(20.0000, 20.0000, 0.0000, 1));
        $final[self::TEST_CASE_NAME_10] = $order;

        //Very bad order 100374806 (prd nn)
        $order = $this->getNewOrderInstance(37130.0100, 32130.0100, 0);
        $this->addItem($order, $this->getItem(19990.0000, 19990.0000, 0.0000, 1));
        $this->addItem($order, $this->getItem(14500.0000, 29.0000, 5000.0000, 500));
        $this->addItem($order, $this->getItem(1000.0100, 1000.0000, 0.0000, 1));
        $this->addItem($order, $this->getItem(1640.0000, 410.0000, 0.0000, 4));
        $final[self::TEST_CASE_NAME_11] = $order;

        //Ошибка в расчете Magento. -1 коп уходили в доставку
        $order = $this->getNewOrderInstance(18189.9900, 13189.9900, 0.0000, 0);
        $this->addItem($order, $this->getItem(7990.0000, 7990.0000, 0, 1.0000));
        $this->addItem($order, $this->getItem(1440.0000, 36.0000, 705.8800, 40.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(1160.0000, 29.0000, 568.6300, 40.0000));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 710.7800, 50.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(360.0000, 36.0000, 176.4700, 10.0000));
        $this->addItem($order, $this->getItem(1800.00, 36.00, 882.35, 50.00));
        $this->addItem($order, $this->getItem(330.0000, 33.0000, 161.7600, 10.0000));
        $this->addItem($order, $this->getItem(720.0000, 36.0000, 352.9400, 20.0000));
        $this->addItem($order, $this->getItem(780.0000, 39.0000, 382.3700, 20.0000));
        $final[self::TEST_CASE_NAME_12] = $order;

        //Ошибка в расчете Magento. -1 коп уходили в доставку. Без большого продукта
        $order = $this->getNewOrderInstance(10199.9900, 5199.9900, 0.0000, 0.00);
        $this->addItem($order, $this->getItem(1440.0000, 36.0000, 705.8800, 40.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(1160.0000, 29.0000, 568.6300, 40.0000));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 710.7800, 50.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(360.0000, 36.0000, 176.4700, 10.0000));
        $this->addItem($order, $this->getItem(1800.0000, 36.0000, 882.3500, 50.0000));
        $this->addItem($order, $this->getItem(330.0000, 33.0000, 161.7600, 10.0000));
        $this->addItem($order, $this->getItem(720.0000, 36.0000, 352.9400, 20.0000));
        $this->addItem($order, $this->getItem(780.0000, 39.0000, 382.3700, 20.0000));
        $final[self::TEST_CASE_NAME_13] = $order;

        //Такой же как и предыдущий, только копейка в +
        $order = $this->getNewOrderInstance(18190.0100, 13190.0100, 0.0000, 0);
        $this->addItem($order, $this->getItem(7990.0000, 7990.0000, 0, 1.0000));
        $this->addItem($order, $this->getItem(1440.0000, 36.0000, 705.8800, 40.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(1160.0000, 29.0000, 568.6300, 40.0000));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 710.7800, 50.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(360.0000, 36.0000, 176.4700, 10.0000));
        $this->addItem($order, $this->getItem(1800.0000, 36.0000, 882.3500, 50.0000));
        $this->addItem($order, $this->getItem(330.0000, 33.0000, 161.7600, 10.0000));
        $this->addItem($order, $this->getItem(720.0000, 36.0000, 352.9400, 20.0000));
        $this->addItem($order, $this->getItem(780.0000, 39.0000, 382.3700, 20.0000));
        $final[self::TEST_CASE_NAME_14] = $order;

        //тестируем размазывание мелких ревардов
        $order = $this->getNewOrderInstance(18190.0000, 13189.6900, 0.0000, 0.31);
        $this->addItem($order, $this->getItem(7990.0000, 7990.0000, 0, 1.0000));
        $this->addItem($order, $this->getItem(1440.0000, 36.0000, 705.8800, 40.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(1160.0000, 29.0000, 568.6300, 40.0000));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 710.7800, 50.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(360.0000, 36.0000, 176.4700, 10.0000));
        $this->addItem($order, $this->getItem(1800.0000, 36.0000, 882.3500, 50.0000));
        $this->addItem($order, $this->getItem(330.0000, 33.0000, 161.7600, 10.0000));
        $this->addItem($order, $this->getItem(720.0000, 36.0000, 352.9400, 20.0000));
        $this->addItem($order, $this->getItem(780.0000, 39.0000, 382.3700, 20.0000));
        $final[self::TEST_CASE_NAME_15] = $order;

        //тестируем размазывание мелких ревардов
        $order = $this->getNewOrderInstance(10200, 5190.0100, 0.0000, 9.99);
        $this->addItem($order, $this->getItem(1440.0000, 36.0000, 705.8800, 40.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(1160.0000, 29.0000, 568.6300, 40.0000));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 710.7800, 50.0000));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 529.4100, 30.0000));
        $this->addItem($order, $this->getItem(360.0000, 36.0000, 176.4700, 10.0000));
        $this->addItem($order, $this->getItem(1800.0000, 36.0000, 882.3500, 50.0000));
        $this->addItem($order, $this->getItem(330.0000, 33.0000, 161.7600, 10.0000));
        $this->addItem($order, $this->getItem(720.0000, 36.0000, 352.9400, 20.0000));
        $this->addItem($order, $this->getItem(780.0000, 39.0000, 382.3700, 20.0000));
        $final[self::TEST_CASE_NAME_16] = $order;

        //Случай совсем из фантастики
        $order = $this->getNewOrderInstance(12989.9900, 7989.9900, 0.0000, 0);
        $this->addItem($order, $this->getItem(5000.0000, 50.0000, 5000.0000, 100.0000));
        $this->addItem($order, $this->getItem(7990.0000, 7990.0000, 0, 1.0000));
        $final[self::TEST_CASE_NAME_17] = $order;

        $order = $this->getNewOrderInstance(4430.0000, 4297.3400, 0.0000, 0);
        $this->addItem($order, $this->getItem(548.0000, 548.0000, 16.3400, 1.0000));
        $this->addItem($order, $this->getItem('', '', 0.0000, 1.0000));
        $this->addItem($order, $this->getItem(815.0000, 815.0000, 24.3800, 1.0000));
        $this->addItem($order, $this->getItem(2693.0000, 2693.0000, 80.7500, 1.0000));
        $this->addItem($order, $this->getItem(374.0000, 374.0000, 11.1900, 1.0000));
        $final[self::TEST_CASE_NAME_18] = $order;

        //Стоимость доставки 315 Р надо распределить по товарам.
        $order = $this->getNewOrderInstance(14356.6000, 14671.6000, 0.0000, -315.0000);
        $this->addItem($order, $this->getItem(5600.0000, 1120.0000, 0.0000, 5.0000));
        $this->addItem($order, $this->getItem(8225.1000, 2741.7000, 0.0000, 3.0000));
        $this->addItem($order, $this->getItem(531.5000, 531.5000, 0.0000, 1.0000));
        $final[self::TEST_CASE_NAME_19] = $order;

        $order = $this->getNewOrderInstance(19160.0100, 14160.0100, 0.0000, 0);
        $this->addItem($order, $this->getItem(2900.0000, 29.0000, -0.0100, 100.0000, 100589));
        $this->addItem($order, $this->getItem(2000.0000, 40.00000, 0, 50.0000, 100590));
        $this->addItem($order, $this->getItem(1450.0000, 29.0000, 0, 50.0000, 100591));
        $this->addItem($order, $this->getItem(1080.0000, 36.0000, 0, 30.0000, 100592));
        $this->addItem($order, $this->getItem(8990.0000, 8990.0000, 5000.0000, 1.0000, 100593));
        $this->addItem($order, $this->getItem(2000.0000, 40.0000, 0, 50.0000, 100594));
        $this->addItem($order, $this->getItem(740.0000, 37.0000, 0, 20.0000, 100595));
        $final[self::TEST_CASE_NAME_20] = $order;

        return $final;
    }
}
