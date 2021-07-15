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
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

class UpdateTimeoutFilter implements CustomFilterInterface
{
    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $timeout = $filter->getValue();

        $attemptTable = $collection->getTable('mygento_kkm_transaction_attempt');

        $conditions[] = sprintf(
            'main_table.%s = %s.%s',
            TransactionInterface::ORDER_ID,
            $attemptTable,
            TransactionAttemptInterface::ORDER_ID
        );
        $conditions[] = sprintf(
            'main_table.%s = %s.%s',
            TransactionInterface::TXN_TYPE,
            $attemptTable,
            TransactionAttemptInterface::TXN_TYPE
        );
        $conditions[] = sprintf(
            '%s.%s = %s',
            $attemptTable,
            TransactionAttemptInterface::OPERATION,
            UpdateRequestInterface::UPDATE_OPERATION_TYPE
        );
        $conditions[] = sprintf(
            '%s.%s > %s',
            $attemptTable,
            TransactionAttemptInterface::UPDATED_AT,
            $collection->getConnection()->quote($timeout)
        );

        $collection->getSelect()
            ->joinLeft(
                $attemptTable,
                implode(' AND ', $conditions),
                []
            )
            ->where(sprintf(
                '%s.%s IS NULL',
                $attemptTable,
                TransactionAttemptInterface::ID
            ))
            ->group(TransactionInterface::TXN_ID);

        return true;
    }
}
