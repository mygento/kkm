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
$failedOrderStatus = 'kkm_failed';

$dataStatus[] = [
    'status' => $failedOrderStatus,
    'label'  => 'KKM Failed'
];
$dataStatusState = [
    [
        'status'     => $failedOrderStatus,
        'state'      => 'processing',
        'is_default' => 0
    ],
    [
        'status'     => $failedOrderStatus,
        'state'      => 'complete',
        'is_default' => 0
    ],
    [
        'status'     => $failedOrderStatus,
        'state'      => 'closed',
        'is_default' => 0
    ]
];

$installer->getConnection()->insertOnDuplicate($statusTable, $dataStatus, ['status', 'label']);
$installer->getConnection()->insertOnDuplicate($statusStateTable, $dataStatusState, ['status', 'state', 'is_default']);


$installer->endSetup();
