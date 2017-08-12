<?php

require 'bootstrap.php';

/**
 *
 *
 * @category Mygento
 * @package Mygento_KKM
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Helper_DiscountTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        if (!class_exists('Varien_Object')) {
            throw new Exception('Varien_Object `Mage` not found.');
        }
    }

    public function test()
    {
        $a = new  Varien_Object();
        $a->setData([
                        'id'   => 1,
                        'type' => 'OloloType'
                    ]);

        $discountHelp = new Mygento_Kkm_Helper_Discount();
//            Mage::helper('kkm/discount');
        $this->assertTrue(method_exists($discountHelp, 'getRecalculated'));
        $this->assertTrue(method_exists($discountHelp, 'getRecalculated'));
        $this->assertTrue(method_exists($discountHelp, 'getRecalculated'));
        $this->assertTrue(method_exists($discountHelp, 'getRecalculated'));
        $this->assertArrayHasKey('type', $a->getData());
        echo 'Key type exists.';
    }
}
