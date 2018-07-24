<?php

//Run tests on installed Magento

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

if (!class_exists('Mage_Core_Helper_Abstract')) {
    class Mage_Core_Helper_Abstract
    {
    }
}

// @codingStandardsIgnoreStart
chdir(__DIR__);
require_once './../vendor/autoload.php';
require_once './DiscountGeneralTestCase.php';
require_once './lib/Object.php';
require_once './lib/Mage.php';

ini_set('display_errors', 0);

// @codingStandardsIgnoreEnd