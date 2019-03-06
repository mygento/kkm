<?php

namespace Mygento\Kkm\Model\ResourceModel;

class TransactionAttempt extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mygento_kkm_transaction_attempt', 'id');
    }
}
