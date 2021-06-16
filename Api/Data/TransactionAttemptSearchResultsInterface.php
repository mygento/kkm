<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

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
