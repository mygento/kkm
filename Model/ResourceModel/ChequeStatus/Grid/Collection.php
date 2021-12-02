<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel\ChequeStatus\Grid;

use Magento\Sales\Model\ResourceModel\Order\Invoice\Grid\Collection as ParentCollection;
use Mygento\Kkm\Model\Source\SalesEntityType;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

class Collection extends ParentCollection
{
    private const ENTITY_LAST_ATTEMPT_ALIAS = 'entity_last_attempt';
    private const SALES_ORDER_TABLE_ALIAS = 'sales_order';

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
        $attemptTableAlias = 'transaction_attempt';
        $isClosedExpression = new \Zend_Db_Expr(
            "IF(({$attemptTableAlias}.operation = 1 OR {$attemptTableAlias}.operation = 5) 
            AND {$attemptTableAlias}.status = 4, 1, 0)"
        );

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
        $attemptTableAlias = 'transaction_attempt';
        $isClosedExpression = new \Zend_Db_Expr(
            "IF({$attemptTableAlias}.operation = 2 AND {$attemptTableAlias}.status = 4, 1, 0)"
        );

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
        $attemptTableAlias = 'transaction_attempt';
        $attemptTable = $this->getTable('mygento_kkm_transaction_attempt');
        $orderTable = $this->getTable('sales_order');

        return $this->getConnection()->select()->from(
            ['entity_table' => $this->getTable($entityTableName)],
            [
                'entity_id' => new \Zend_Db_Expr('CONCAT("' . $entityType .'", "_", entity_table.entity_id)'),
                'sales_entity_type' => new \Zend_Db_Expr("CONCAT('$entityType')"),
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
            [$attemptTableAlias => $attemptTable],
            self::ENTITY_LAST_ATTEMPT_ALIAS . '.last_attempt_id = ' . $attemptTableAlias . '.id',
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
                'last_attempt_id' => new \Zend_Db_Expr('max(id)')
            ]
        )->where('operation != ?', UpdateRequestInterface::UPDATE_OPERATION_TYPE)
            ->group('sales_entity_id');
    }
}
