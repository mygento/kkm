<?php
$installer = $this;

$installer->startSetup();
$tablePath = 'kkm/token';

$installer->getConnection()->dropTable($this->getTable($tablePath));

$installer->endSetup();
