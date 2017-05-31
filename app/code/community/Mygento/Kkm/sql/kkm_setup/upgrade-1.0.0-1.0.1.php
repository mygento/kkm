<?php
/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->dropTable($installer->getTable('kkm/status'));

$kkm_status_table = $installer->getConnection()
    ->newTable($installer->getTable('kkm/status'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        ['unsigned'       => true,
        'nullable'       => false,
        'primary'        => true,
        'auto_increment' => true
        ], 'ID')
    ->addColumn('uuid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Universally Unique Identifier')
    ->addColumn('external_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'External Id')
    ->addColumn('operation', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Operation')
    ->addColumn('vendor', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Vendor code')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, null,
        [ 
        'nullable' => false,
        'length' => 255 
        ], 'status');

$installer->getConnection()->createTable($kkm_status_table);

$installer->endSetup();
