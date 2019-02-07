<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_payment_transaction'),
            'kkm_status',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => '16',
                'comment' => 'Status. For KKM transactions',
                'after' => 'is_closed'
            ]
        );

        $installer->endSetup();
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     */
    public function installVer201($installer)
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

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $installer
     */
    public function installVer202($installer)
    {
        /**
         * Create table 'mygento_kkm_log'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mygento_kkm_log'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity'       => true, 'auto_increment' => true, 'unsigned'       => true,
                 'nullable'       => false, 'primary'        => true],
                'ID'
            )
            ->addColumn(
                'message',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Message'
            )
            ->addColumn(
                'severity',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                [],
                'Severity'
            )
            ->addColumn(
                'timestamp',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                ],
                'Time'
            )
            ->addColumn(
                'advanced_info',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Advanced Info'
            )
            ->addColumn(
                'module_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Module Code'
            );
        $installer->getConnection()->createTable($table);
    }
}
