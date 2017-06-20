<?php
$installer = $this;

$installer->startSetup();
$tablePath = 'kkm/token';

if ($installer->tableExists($tablePath)) {
    $installer->getConnection()->dropTable($this->getTable($tablePath));
}

$installer->endSetup();
