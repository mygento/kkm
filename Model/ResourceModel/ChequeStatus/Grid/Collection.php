<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel\ChequeStatus\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Grid\Collection as ParentCollection;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Model\Source\SalesEntityType;
use Psr\Log\LoggerInterface as Logger;

class Collection extends ParentCollection
{
    /**
     * @var ExpressionFactory
     */
    private $expressionFactory;

    /**
     * @var string
     */
    private $attemptTableName = 'mygento_kkm_transaction_attempt';

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
     * @throws \Zend_Db_Select_Exception
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $union = $this->getConnection()->select()->union(
            [
                $this->buildInvoiceSelect(),
                $this->buildCreditmemoSelect(),
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
        $isClosedCondition =
            "({$this->attemptTableName}.operation = " . RequestInterface::SELL_OPERATION_TYPE . " 
            OR {$this->attemptTableName}.operation = " . RequestInterface::RESELL_SELL_OPERATION_TYPE . ") 
            AND {$this->attemptTableName}.status = " . TransactionAttemptInterface::STATUS_DONE;

        $isClosedExpression = $this->expressionFactory->create([
            'expression' => "IF(${isClosedCondition}, 1, 0)",
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
        $isClosedCondition =
            "{$this->attemptTableName}.operation = " . RequestInterface::REFUND_OPERATION_TYPE . " 
            AND {$this->attemptTableName}.status = " . TransactionAttemptInterface::STATUS_DONE;

        $isClosedExpression = $this->expressionFactory->create([
            'expression' => "IF(${isClosedCondition} , 1, 0)",
        ]);

        return $this->buildEntitySelect(
            'sales_creditmemo',
            SalesEntityType::ENTITY_TYPE_CREDITMEMO,
            $isClosedExpression
        );
    }

    /**
     * @param $entityTable
     * @param $entityType
     * @param $isClosedExpression
     * @return \Magento\Framework\DB\Select
     */
    private function buildEntitySelect($entityTable, $entityType, $isClosedExpression)
    {
        $entityLastAttemptAlias = 'entity_last_attempt';
        $orderTableName = $this->getTable('sales_order');
        $entityTableName = $this->getTable($entityTable);

        return $this->getConnection()->select()->from(
            $entityTableName,
            [
                'entity_id' => $this->getConnection()->getConcatSql(
                    [$this->getConnection()->quote($entityType), "${entityTableName}.entity_id"],
                    '_'
                ),
                'sales_entity_type' => $this->expressionFactory->create(['expression' => "'${entityType}'"]),
                'sales_entity_id' => "${entityTableName}.entity_id",
                'increment_id',
                'store_title' => 'store_id',
                'store_id',
                'order_id',
            ]
        )->joinLeft(
            $orderTableName,
            "order_id = ${orderTableName}.entity_id",
            [
                'order_entity_id' => 'entity_id',
                'order_increment_id' => 'increment_id',
            ]
        )->joinLeft(
            [$entityLastAttemptAlias => $this->buildEntityLastAttemptSelect()],
            "${entityTableName}.entity_id = ${entityLastAttemptAlias}.sales_entity_id",
            []
        )->joinLeft(
            $this->attemptTableName,
            "${entityLastAttemptAlias}.last_attempt_id = {$this->attemptTableName}.id",
            [
                'last_attempt_id' => 'id',
                'last_attempt_operation' => 'operation',
                'last_attempt_status' => 'status',
                'error_code',
                'error_type',
                'is_closed' => $isClosedExpression,
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
                    'expression' => 'max(id)',
                ]),
            ]
        )->where('operation != ?', UpdateRequestInterface::UPDATE_OPERATION_TYPE)
            ->group('sales_entity_id');
    }
}
