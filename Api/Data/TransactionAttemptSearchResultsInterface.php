<?php

namespace Mygento\Kkm\Api\Data;

interface TransactionAttemptSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get list of TransactionAttempt
     * @return \Mygento\Kkm\Api\Data\TransactionAttemptInterface[]
     */
    public function getItems();

    /**
     * Set list of TransactionAttempt
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface[] $items
     */
    public function setItems(array $items);
}
