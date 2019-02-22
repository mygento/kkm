<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Mygento\Kkm\Helper\Data as KkmHelper;

/**
 * Class UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        //handle all possible upgrade versions

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            //code to upgrade to 2.0.1
            $this->upgradeToVer201($installer);
        }

        $setup->endSetup();
    }

    /**
     * Upgrade to version 2.0.1
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     */
    public function upgradeToVer201($installer)
    {
        // Required tables
        $statusTable      = $installer->getTable('sales_order_status');
        $statusStateTable = $installer->getTable('sales_order_status_state');

        // Insert statuses
        $installer->getConnection()->insertArray(
            $statusTable,
            [
            'status',
            'label'
            ],
            [
            ['status' => KkmHelper::ORDER_KKM_FAILED_STATUS, 'label' => 'KKM Failed'],
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
                'status'     => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state'      => 'processing',
                'is_default' => 0
            ],
            [
                'status'     => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state'      => 'complete',
                'is_default' => 0
            ],
            [
                'status'     => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state'      => 'closed',
                'is_default' => 0
            ]
            ]
        );
    }
}
