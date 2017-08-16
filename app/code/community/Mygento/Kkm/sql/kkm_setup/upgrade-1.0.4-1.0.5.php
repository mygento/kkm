<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
$installer = $this;
$tbl       = $installer->getTable('kkm/status');

$installer->getConnection()->addColumn($tbl, 'entity_type', [
    'type'    => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Entity Type'
]);
$installer->getConnection()->addColumn($tbl, 'increment_id', [
    'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable' => true,
    'comment'  => 'Increment Id of the entity'
]);
$installer->getConnection()->addColumn($tbl, 'resend_count', [
    'type'    => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'comment' => 'Count of attempts to resend cheque to KKM'
]);
$installer->getConnection()->addColumn($tbl, 'short_status', [
    'type'    => Varien_Db_Ddl_Table::TYPE_TEXT,
    'comment' => 'Last status of the transaction on KKM side (wait/done/fail)'
]);
$installer->getConnection()->addColumn($tbl, 'created_at', [
    'type'    => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
    'comment' => 'Created At'
]);
$installer->getConnection()->addColumn($tbl, 'updated_at', [
    'type'    => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
    'comment' => 'Updated At'
]);

Mage::getModel('kkm/status')->getCollection();

$installer->startSetup();
$installer->endSetup();