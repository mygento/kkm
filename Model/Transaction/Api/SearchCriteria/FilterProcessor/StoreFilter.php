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
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Helper\Data as KkmHelper;

class StoreFilter implements CustomFilterInterface
{
    /**
     * @var KkmHelper
     */
    protected $kkmHelper;

    public function __construct(KkmHelper $kkmHelper)
    {
        $this->kkmHelper = $kkmHelper;
    }
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
                'sales_order',
                sprintf(
                    'main_table.%s = sales_order.%s',
                    TransactionInterface::ORDER_ID,
                    OrderInterface::ENTITY_ID
                ),
                []
            )
            ->where(sprintf(
                'sales_order.%s = %s',
                OrderInterface::STORE_ID,
                $storeId
            ));

        if ($this->kkmHelper->isMessageQueueEnabled($storeId)) {
            $alias = 't_mygento_kkm_transaction_attempt';
            $conditions[] = sprintf(
                'main_table.%s = %s.%s',
                TransactionInterface::ORDER_ID,
                $alias,
                TransactionAttemptInterface::ORDER_ID
            );
            $conditions[] = sprintf(
                'main_table.%s = %s.%s',
                TransactionInterface::TXN_TYPE,
                $alias,
                TransactionAttemptInterface::TXN_TYPE
            );
            $conditions[] = sprintf(
                '%s.%s = %s',
                $alias,
                TransactionAttemptInterface::OPERATION,
                UpdateRequestInterface::UPDATE_OPERATION_TYPE
            );
            $conditions[] = sprintf(
                '%s.%s > %s',
                $alias,
                TransactionAttemptInterface::UPDATED_AT,
                $collection->getConnection()->quote((new \DateTime('-1 hour'))->format(Mysql::TIMESTAMP_FORMAT))
            );

            $collection->getSelect()
                ->joinLeft(
                    [$alias => $collection->getTable('mygento_kkm_transaction_attempt')],
                    implode(' AND ', $conditions),
                    []
                )
                ->where(sprintf(
                    '%s.%s IS NULL',
                    $alias,
                    TransactionAttemptInterface::ID
                ));
        }
        $collection->getSelect()->group(TransactionInterface::TXN_ID);

        return true;
    }
}
