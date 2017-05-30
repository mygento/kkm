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
        ['status' => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS, 'label' => 'KKM Failed'],
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
            'status' => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
            'state' => 'processing',
            'is_default' => 0
        ],
        [
            'status' => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
            'state' => 'complete',
            'is_default' => 0
        ],
        [
            'status' => Mygento_Kkm_Model_Abstract::ORDER_KKM_FAILED_STATUS,
            'state' => 'closed',
            'is_default' => 0
        ]
    ]
);

$installer->endSetup();
