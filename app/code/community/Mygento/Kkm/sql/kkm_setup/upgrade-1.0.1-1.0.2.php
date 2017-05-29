<?php
$installer = $this;
$installer->startSetup();

// Required tables
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');

// Insert statuses
$installer->getConnection()->insertArray(
    $statusTable,
    [
        'status',
        'label'
    ],
    [
        ['status' => 'kkm_failed', 'label' => 'KKM Failed'],
    ]
);

$installer->getConnection()->insertArray(
    $statusStateTable,
    [
        'status',
        'state',
        'is_default'
    ],
    [
        [
            'status' => 'kkm_failed',
            'state' => 'processing',
            'is_default' => 0
        ]
    ]
);

$installer->endSetup();
