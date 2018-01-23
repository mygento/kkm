<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */

$installer = $this;
$installer->startSetup();

$tbl = $installer->getTable('kkm/status');

$installer->getConnection()->changeColumn($tbl, 'entity_type', 'entity_type', [
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 50,
    'comment' => 'Entity Type'
]);

$installer->getConnection()->changeColumn($tbl, 'increment_id', 'increment_id', [
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length'    => 50,
    'comment'  => 'Increment Id of the entity'
]);

$installer->getConnection()->addIndex(
        $tbl, $installer->getIdxName(
                'kkm/status',
                ['entity_type', 'increment_id'],
                Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        ['entity_type', 'increment_id'],
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

$installer->endSetup();