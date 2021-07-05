<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Data\Collection;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionAttemptRepository implements \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
{
    /** @var \Mygento\Kkm\Model\ResourceModel\TransactionAttempt */
    private $resource;

    /** @var \Mygento\Kkm\Model\ResourceModel\TransactionAttempt\CollectionFactory */
    private $collectionFactory;

    /** @var \Mygento\Kkm\Api\Data\TransactionAttemptInterfaceFactory */
    private $entityFactory;

    /** @var \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterfaceFactory */
    private $searchResultsFactory;

    /**
     * @param \Mygento\Kkm\Model\ResourceModel\TransactionAttempt $resource
     * @param \Mygento\Kkm\Model\ResourceModel\TransactionAttempt\CollectionFactory $collectionFactory
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterfaceFactory $entityFactory
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterfaceFactory $searchResFactory
     */
    public function __construct(
        ResourceModel\TransactionAttempt $resource,
        ResourceModel\TransactionAttempt\CollectionFactory $collectionFactory,
        \Mygento\Kkm\Api\Data\TransactionAttemptInterfaceFactory $entityFactory,
        \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterfaceFactory $searchResFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->entityFactory = $entityFactory;
        $this->searchResultsFactory = $searchResFactory;
    }

    /**
     * @param int $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function getById($entityId)
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Kkm Transaction Attempt with id "%1" does not exist.', $entityId)
            );
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function getByEntityId($operation, $entityId)
    {
        /** @var ResourceModel\TransactionAttempt\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                TransactionAttemptInterface::OPERATION,
                ['eq' => $operation]
            )
            ->addFieldToFilter(
                TransactionAttemptInterface::SALES_ENTITY_ID,
                ['eq' => $entityId]
            );

        return $collection->getFirstItem();
    }

    /**
     * @inheritDoc
     */
    public function getByIncrementId($operation, $orderId, $entityIncrementId)
    {
        /** @var ResourceModel\TransactionAttempt\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                TransactionAttemptInterface::OPERATION,
                ['eq' => $operation]
            )
            ->addFieldToFilter(
                TransactionAttemptInterface::ORDER_ID,
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                TransactionAttemptInterface::SALES_ENTITY_INCREMENT_ID,
                ['eq' => $entityIncrementId]
            );

        return $collection->getFirstItem();
    }

    /**
     * @inheritDoc
     */
    public function getParentAttempt($entityId): TransactionAttemptInterface
    {
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                TransactionAttemptInterface::OPERATION,
                ['eq' => RequestInterface::RESELL_SELL_OPERATION_TYPE]
            )
            ->addFieldToFilter(
                TransactionAttemptInterface::SALES_ENTITY_ID,
                ['eq' => $entityId]
            );

        if ($collection->getSize() === 0) {
            return $this->getByEntityId(RequestInterface::SELL_OPERATION_TYPE, $entityId);
        }

        if ($collection->getSize() === 1) {
            return $collection->getFirstItem();
        }

        //В бинарном дереве ищем узел без потомков
        $allParentIds = $collection->getColumnValues(TransactionAttemptInterface::PARENT_ID);
        $allIds = $collection->getColumnValues(TransactionAttemptInterface::ID);

        $withoutChildren = array_diff($allIds, $allParentIds);
        $childlessAttemptId = array_shift($withoutChildren);

        return $collection->getItemById($childlessAttemptId);
    }

    /**
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface
     */
    public function save(\Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity)
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __($exception->getMessage())
            );
        }

        return $entity;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @return bool
     */
    public function delete(\Mygento\Kkm\Api\Data\TransactionAttemptInterface $entity)
    {
        try {
            $this->resource->delete($entity);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __($exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @param int $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @return bool
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        /** @var \Mygento\Kkm\Model\ResourceModel\TransactionAttempt\Collection $collection */
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $sortOrders = $criteria->getSortOrders();
        $sortAsc = SortOrder::SORT_ASC;
        $orderAsc = Collection::SORT_ORDER_ASC;
        $orderDesc = Collection::SORT_ORDER_DESC;
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == $sortAsc) ? $orderAsc : $orderDesc
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        /** @var \Mygento\Kkm\Api\Data\TransactionAttemptSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
