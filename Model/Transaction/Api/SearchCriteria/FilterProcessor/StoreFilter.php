<?php

/**
 * @author Mygento Team
 * @copyright 2020-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_LorealShipping
 */

namespace Mygento\Kkm\Model\Transaction\Api\SearchCriteria\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Base\Model\Payment\Transaction;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;

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

        $collection->getSelect()
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
            ->where(sprintf(
                'mygento_kkm_transaction_attempt.%s = %s',
                TransactionAttemptInterface::STORE_ID,
                $storeId
            ))
            ->group(TransactionInterface::TXN_ID);

        return true;
    }
}
