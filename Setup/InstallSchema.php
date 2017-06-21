<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
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
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'mygento_kkm_status'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mygento_kkm_status'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity'       => true, 'auto_increment' => true, 'unsigned'       => true,
                'nullable'       => false, 'primary'        => true],
                'ID'
            )
            ->addColumn(
                'uuid',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [
                'nullable' => false
                ],
                'Universally Unique Identifier'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [
                'nullable' => false
                ],
                'Type Of Operation'
            )
            ->addColumn(
                'increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [
                'nullable' => false
                ],
                'Increment Id'
            )
            ->addColumn(
                'operation',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [
                'nullable' => false
                ],
                'Operation'
            )
            ->addColumn(
                'vendor',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true],
                'Vendor code'
            )
            ->addColumn(
                'response',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [
                'nullable' => false,
                'length'   => 255
                ],
                'Response'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [
                'nullable' => false,
                'length'   => 255
                ],
                'Status'
            )
            ->setComment('Mygento Kkm Status');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
