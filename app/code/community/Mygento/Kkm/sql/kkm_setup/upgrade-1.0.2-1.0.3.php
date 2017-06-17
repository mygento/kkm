<?php
$installer = $this;

$installer->startSetup();
$logTablePath = 'kkm/log_entry';

if (!$installer->tableExists($logTablePath)) {

    $logTable = $installer->getConnection()
        ->newTable($installer->getTable($logTablePath))
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, [
            'unsigned'       => true,
            'nullable'       => false,
            'primary'        => true,
            'auto_increment' => true,
        ], 'ID')
        ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT, null, [], 'Message')
        ->addColumn('severity', Varien_Db_Ddl_Table::TYPE_TINYINT, null, [], 'Severity')
        ->addColumn('timestamp', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
            'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        ], 'Time')
        ->addColumn('advanced_info', Varien_Db_Ddl_Table::TYPE_TEXT, null, [], 'Advanced Info')
        ->addColumn('module_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [], 'Module Code');

    $installer->getConnection()->createTable($logTable);
}

$installer->endSetup();
