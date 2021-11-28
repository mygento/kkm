<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel\ChequeStatus\Grid;

use Magento\Sales\Model\ResourceModel\Order\Invoice\Grid\Collection as ParentCollection;

class Collection extends ParentCollection
{
    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $invoicesSelect = clone $this->getSelect();
        $invoicesSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $invoicesSelect->columns(
            [
                'entity_id' => new \Zend_Db_Expr('CONCAT("invoice", "_", entity_id)'),
                'sales_entity_type' => new \Zend_Db_Expr('CONCAT("invoice")'),
                'sales_entity_id' => 'entity_id',
                'sales_increment_id' => 'increment_id',
                'store_id',
                'order_id'
            ]
        );
        $creditmemoSelect = $this->getConnection()->select()->from(
            $this->getTable('sales_creditmemo'),
            [
                'entity_id' => new \Zend_Db_Expr('CONCAT("creditmemo", "_", entity_id)'),
                'sales_entity_type' => new \Zend_Db_Expr('CONCAT("creditmemo")'),
                'sales_entity_id' => 'sales_creditmemo.entity_id',
                'sales_increment_id' => 'sales_creditmemo.increment_id',
                'store_id',
                'order_id'
            ]
        );

        $union = $this->getConnection()->select()->union([
            $invoicesSelect,
            $creditmemoSelect
        ], \Magento\Framework\DB\Select::SQL_UNION_ALL);

        $this->getSelect()->reset()->from($union);

        return $this;
    }
}
