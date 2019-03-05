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

    /**
     * @param \Mygento\Kkm\Api\Data\TransactionAttemptInterface $object
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _beforeSave($object)
    {
        $trials = $object->getNumberOfTrials();
        $object->setNumberOfTrials(is_null($trials) ? $trials + 1 : 0);

        return parent::_beforeSave($object);
    }
}
