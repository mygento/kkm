<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel\ChequeStatus\Grid;

use Mygento\Kkm\Model\Source\SalesEntityType;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Grid\Collection as ParentCollection;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Psr\Log\LoggerInterface as Logger;

class Collection extends ParentCollection
{
    private const ENTITY_LAST_ATTEMPT_ALIAS = 'entity_last_attempt';
    private const SALES_ORDER_TABLE_ALIAS = 'sales_order';
    private const ATTEMT_TABLE_ALIAS = 'transaction_attempt';

    /**
     * @var ExpressionFactory
     */
    private $expressionFactory;

    /**
     * @param ExpressionFactory $expressionFactory
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        ExpressionFactory $expressionFactory,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'sales_invoice_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order\Invoice::class
    ) {
        $this->expressionFactory = $expressionFactory;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $union = $this->getConnection()->select()->union(
            [
                $this->buildInvoiceSelect(),
                $this->buildCreditmemoSelect()
            ]
        );

        $this->getSelect()->reset()->from($union);

        return $this;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    private function buildInvoiceSelect()
    {
        $isClosedExpression = $this->expressionFactory->create(['expression' =>
            "IF((" . self::ATTEMT_TABLE_ALIAS . ".operation = 1 
            OR " . self::ATTEMT_TABLE_ALIAS . ".operation = 5) 
            AND " . self::ATTEMT_TABLE_ALIAS . ".status = 4, 1, 0)"
        ]);

        return $this->buildEntitySelect(
            'sales_invoice',
            SalesEntityType::ENTITY_TYPE_INVOICE,
            $isClosedExpression
        );
    }


    /**
     * @return \Magento\Framework\DB\Select
     */
    private function buildCreditmemoSelect()
    {
        $isClosedExpression = $this->expressionFactory->create(['expression' =>
            "IF(" . self::ATTEMT_TABLE_ALIAS . ".operation = 2 AND " . self::ATTEMT_TABLE_ALIAS . ".status = 4, 1, 0)"
        ]);

        return $this->buildEntitySelect(
            'sales_creditmemo',
            SalesEntityType::ENTITY_TYPE_CREDITMEMO,
            $isClosedExpression
        );
    }

    /**
     * @param $entityTableName
     * @param $entityType
     * @param $isClosedExpression
     * @return \Magento\Framework\DB\Select
     */
    private function buildEntitySelect($entityTableName, $entityType, $isClosedExpression)
    {
        $attemptTable = $this->getTable('mygento_kkm_transaction_attempt');
        $orderTable = $this->getTable('sales_order');

        return $this->getConnection()->select()->from(
            ['entity_table' => $this->getTable($entityTableName)],
            [
                'entity_id' => $this->getConnection()->getConcatSql(
                    [$this->getConnection()->quote($entityType), 'entity_table.entity_id'],
                    '_'
                ),
                'sales_entity_type' => $this->expressionFactory->create(['expression' => "'$entityType'"]),
                'sales_entity_id' => 'entity_table.entity_id',
                'increment_id',
                'store_id',
                'order_id',
            ]
        )->joinLeft(
            [self::SALES_ORDER_TABLE_ALIAS => $orderTable],
            'order_id = ' . self::SALES_ORDER_TABLE_ALIAS . '.entity_id',
            [
                'order_entity_id' => 'entity_id',
                'order_increment_id' => 'increment_id'
            ]
        )->joinLeft(
            [self::ENTITY_LAST_ATTEMPT_ALIAS => $this->buildEntityLastAttemptSelect()],
            'entity_table.entity_id = ' . self::ENTITY_LAST_ATTEMPT_ALIAS . '.sales_entity_id',
            []
        )->joinLeft(
            [self::ATTEMT_TABLE_ALIAS => $attemptTable],
            self::ENTITY_LAST_ATTEMPT_ALIAS . '.last_attempt_id = ' . self::ATTEMT_TABLE_ALIAS . '.id',
            [
                'last_attempt_id' => 'id',
                'last_attempt_operation' => 'operation',
                'last_attempt_status' => 'status',
                'error_code',
                'error_type',
                'is_closed' => $isClosedExpression
            ]
        );
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    private function buildEntityLastAttemptSelect()
    {
        $attemptTable = $this->getTable('mygento_kkm_transaction_attempt');

        return $this->getConnection()->select()->from(
            $attemptTable,
            [
                'sales_entity_id',
                'last_attempt_id' => $this->expressionFactory->create([
                    'expression' => 'max(id)'
                ])
            ]
        )->where('operation != ?', UpdateRequestInterface::UPDATE_OPERATION_TYPE)
            ->group('sales_entity_id');
    }
}
