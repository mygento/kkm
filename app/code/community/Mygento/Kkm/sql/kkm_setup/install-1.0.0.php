<?php
/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->dropTable('kkm/token');

$kkm_token_table = $installer->getConnection()
    ->newTable($installer->getTable('kkm/token'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
        [
        'unsigned'       => true,
        'nullable'       => false,
        'primary'        => true,
        'auto_increment' => true
        ], 'ID')
    ->addColumn('vendor', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Vendor code')
    ->addColumn('token', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
        [
        'nullable' => false
        ], 'Token key')
    ->addColumn('expire_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
    [
    'nullable' => false
    ], 'Expire date token');

$installer->getConnection()->createTable($kkm_token_table);

$installer->endSetup();
