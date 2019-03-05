<?php

namespace Mygento\Kkm\Model\ResourceModel\TransactionAttempt;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Mygento\Kkm\Model\TransactionAttempt::class,
            \Mygento\Kkm\Model\ResourceModel\TransactionAttempt::class
        );
    }
}
