<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
$installer = $this;

$installer->startSetup();
$tablePath = 'kkm/token';

$installer->getConnection()->dropTable($this->getTable($tablePath));

$installer->endSetup();
