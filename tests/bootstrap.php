<?php

//Run tests on installed Magento

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
// @codingStandardsIgnoreStart
chdir(__DIR__);
require_once './../app/bootstrap.php';
require_once './../vendor/autoload.php';
require_once './../app/Mage.php';
Mage::app();

ini_set('display_errors', 0);

// @codingStandardsIgnoreEnd