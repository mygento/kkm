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

        $collection->getSelect()
            ->join(
                $collection->getTable('mygento_kkm_transaction_attempt'),
                sprintf(
                    'main_table.%s = mygento_kkm_transaction_attempt.%s',
                    TransactionInterface::ORDER_ID,
                    TransactionAttemptInterface::ORDER_ID
                ),
                []
            )
            ->where(sprintf(
                'mygento_kkm_transaction_attempt.%s = %s',
                TransactionAttemptInterface::STORE_ID,
                $storeId
            ));

        return true;
    }
}
