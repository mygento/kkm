<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
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
            ['status' => \Mygento\Kkm\Model\AbstractModel::ORDER_KKM_FAILED_STATUS, 'label' => 'KKM Failed'],
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
                'status'     => \Mygento\Kkm\Model\AbstractModel::ORDER_KKM_FAILED_STATUS,
                'state'      => 'processing',
                'is_default' => 0
            ],
            [
                'status'     => \Mygento\Kkm\Model\AbstractModel::ORDER_KKM_FAILED_STATUS,
                'state'      => 'complete',
                'is_default' => 0
            ],
            [
                'status'     => \Mygento\Kkm\Model\AbstractModel::ORDER_KKM_FAILED_STATUS,
                'state'      => 'closed',
                'is_default' => 0
            ]
            ]
        );
    }
}
