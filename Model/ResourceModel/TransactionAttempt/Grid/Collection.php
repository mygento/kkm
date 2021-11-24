<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel\TransactionAttempt\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Model\ResourceModel\TransactionAttempt\Collection as ParentCollection;

class Collection extends ParentCollection implements SearchResultInterface
{
    private const OPERATIONS_TO_SHOW = [
        RequestInterface::SELL_OPERATION_TYPE,
        RequestInterface::REFUND_OPERATION_TYPE,
        RequestInterface::RESELL_REFUND_OPERATION_TYPE,
        RequestInterface::RESELL_SELL_OPERATION_TYPE,
    ];

    /** @var \Magento\Framework\Api\Search\AggregationInterface */
    protected $aggregations;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Magento\Framework\DB\Sql\ExpressionFactory $expressionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|string|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        string $mainTable,
        string $eventPrefix,
        string $eventObject,
        string $resourceModel,
        string $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $successfulKkmAttemptsAlias = 'successful_kkm_attempts';
        $isClosedExpression = new \Zend_Db_Expr(
            '(' . $successfulKkmAttemptsAlias . '_' . TransactionAttemptInterface::ID . ' IS NOT NULL)'
        );
        $successfulKkmAttemptId = $successfulKkmAttemptsAlias . '_' . TransactionAttemptInterface::ID;
        $successfulKkmAttemptOrderId = $successfulKkmAttemptsAlias . '_' . TransactionAttemptInterface::ORDER_ID;
        $successfulKkmAttemptOperation = $successfulKkmAttemptsAlias . '_' . TransactionAttemptInterface::OPERATION;
        $successfulKkmAttemptSalesEntityId = $successfulKkmAttemptsAlias . '_' . TransactionAttemptInterface::SALES_ENTITY_ID;

        $successfulKkmAttemptsSelect = $this->getConnection()->select();
        $successfulKkmAttemptsSelect->from(
            [$successfulKkmAttemptsAlias => $this->getMainTable()],
            [
                $successfulKkmAttemptId => new \Zend_Db_Expr('MIN(' . TransactionAttemptInterface::ID . ')'),
                $successfulKkmAttemptOrderId => TransactionAttemptInterface::ORDER_ID,
                $successfulKkmAttemptOperation => TransactionAttemptInterface::OPERATION,
                $successfulKkmAttemptSalesEntityId => TransactionAttemptInterface::SALES_ENTITY_ID,
            ]
        )->where(TransactionAttemptInterface::STATUS . '= ?', TransactionAttemptInterface::STATUS_DONE)
            ->group(TransactionAttemptInterface::ORDER_ID)
            ->group(TransactionAttemptInterface::OPERATION)
            ->group(TransactionAttemptInterface::SALES_ENTITY_ID);

        $this->getSelect()
            ->joinLeft(
                [$successfulKkmAttemptsAlias => $successfulKkmAttemptsSelect],
                implode(
                    ' AND ',
                    [
                        TransactionAttemptInterface::ORDER_ID . " = {$successfulKkmAttemptOrderId}" ,
                        TransactionAttemptInterface::OPERATION . " = {$successfulKkmAttemptOperation}",
                        TransactionAttemptInterface::SALES_ENTITY_ID . " = {$successfulKkmAttemptSalesEntityId}",
                    ]
                ),
                ['is_closed' => $isClosedExpression]
            )->where(
                TransactionAttemptInterface::OPERATION . ' IN(?)',
                self::OPERATIONS_TO_SHOW
            );

        $this->addFilterToMap('is_closed', $isClosedExpression);

        return $this;
    }
}
