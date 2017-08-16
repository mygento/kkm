<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
$installer = $this;
$installer->startSetup();

// Required tables
$statusTable      = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');

$dataStatus[] = [
    'status' => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
    'label'  => 'KKM Failed'
];
$dataStatusState = [
    [
        'status'     => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
        'state'      => 'processing',
        'is_default' => 0
    ],
    [
        'status'     => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
        'state'      => 'complete',
        'is_default' => 0
    ],
    [
        'status'     => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
        'state'      => 'closed',
        'is_default' => 0
    ]
];

$installer->getConnection()->insertOnDuplicate($statusTable, $dataStatus, ['status', 'label']);
$installer->getConnection()->insertOnDuplicate($statusStateTable, $dataStatusState, ['status', 'state', 'is_default']);


$installer->endSetup();
