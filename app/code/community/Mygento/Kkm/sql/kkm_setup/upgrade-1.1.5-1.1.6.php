<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */

$installer = $this;
$installer->startSetup();

$tbl = $installer->getTable('kkm/log_entry');

$installer->getConnection()->addColumn($tbl, 'created_at', [
    'type'      => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'nullable'      => false,
    'default'      => '0000-00-00 00:00:00',
    'comment' => 'Created At'
]);

$installer->getConnection()->dropColumn($tbl, 'timestamp');

$installer->endSetup();