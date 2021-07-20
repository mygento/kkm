<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Transaction\Api\SearchCriteria\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Model\Atol\Request;
use Zend\Db\Sql\Select;

class StoreFilter implements CustomFilterInterface
{
    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $storeId = $filter->getValue();

        $attemptTable = $collection->getTable('mygento_kkm_transaction_attempt');

        $conditions[] = sprintf(
            'mygento_kkm_transaction_attempt.%s = %s',
            TransactionAttemptInterface::STORE_ID,
            $storeId
        );
        $conditions[] = sprintf(
            '%s.%s in (%s, %s)',
            $attemptTable,
            TransactionAttemptInterface::OPERATION,
            Request::SELL_OPERATION_TYPE,
            Request::REFUND_OPERATION_TYPE
        );

        $collection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns('main_table.txn_id')
            ->join(
                $attemptTable,
                sprintf(
                    'main_table.%s = %s.%s',
                    TransactionInterface::ORDER_ID,
                    $attemptTable,
                    TransactionAttemptInterface::ORDER_ID
                ),
                []
            )
            ->where(implode(' AND ', $conditions))
            ->group(TransactionInterface::TXN_ID);

        return true;
    }
}
