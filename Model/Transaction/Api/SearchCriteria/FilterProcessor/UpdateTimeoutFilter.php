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
use Zend\Db\Sql\Select;

class UpdateTimeoutFilter implements CustomFilterInterface
{
    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $connection = $collection->getConnection();

        $timeout = $filter->getValue();

        $attemptTable = $collection->getTable('mygento_kkm_transaction_attempt');

        $conditions[] = 'main_table.' . TransactionInterface::ORDER_ID .
            ' = ' . $attemptTable . '.' . TransactionAttemptInterface::ORDER_ID;

        $conditions[] = 'main_table.' . TransactionInterface::TXN_TYPE .
            ' = ' . $attemptTable . '.' . TransactionAttemptInterface::TXN_TYPE;

        $conditions[] = $connection->prepareSqlCondition(
            $attemptTable . '.' . TransactionAttemptInterface::OPERATION,
            UpdateRequestInterface::UPDATE_OPERATION_TYPE);

        $conditions[] = $connection->prepareSqlCondition(
            $attemptTable . '.' . TransactionAttemptInterface::UPDATED_AT,
            ['gt' => $timeout]);

        $collection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns('main_table.' . TransactionInterface::TXN_ID)
            ->joinLeft(
                $attemptTable,
                implode(' AND ', $conditions),
                []
            )
            ->where($attemptTable . '.' . TransactionAttemptInterface::ID . ' IS NULL')
            ->group(TransactionInterface::TXN_ID);

        return true;
    }
}
