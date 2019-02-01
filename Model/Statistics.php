<?php

namespace Mygento\Kkm\Model;

use \Magento\Sales\Api\Data\TransactionInterface;

class Statistics extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var TransactionInterface[]
     */
    protected $fails     = [];
    /**
     * @var TransactionInterface[]
     */
    protected $unknown   = [];
    /**
     * @var TransactionInterface[]
     */
    protected $wait      = [];
    protected $doneCount = 0;
    protected $fromDate;
    protected $toDate;

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     */
    public function addFail(TransactionInterface $transaction)
    {
        $this->fails[] = $transaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     */
    public function addUnknown(TransactionInterface $transaction)
    {
        $this->unknown[] = $transaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     */
    public function addWait(TransactionInterface $transaction)
    {
        $this->wait[] = $transaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     */
    public function addDone(TransactionInterface $transaction)
    {
        $this->doneCount++;
    }

    /**
     * @param mixed $fromDate
     * @return $this
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    /**
     * @param mixed $toDate
     * @return $this
     */
    public function setToDate($toDate)
    {
        $this->toDate = $toDate;

        return $this;
    }

    /**
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getFails()
    {
        return $this->fails;
    }

    /**
     * @return int
     */
    public function getFailsCount()
    {
        return count($this->fails);
    }

    /**
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getUnknowns()
    {
        return $this->unknown;
    }

    /**
     * @return int
     */
    public function getUnknownsCount()
    {
        return count($this->unknown);
    }

    /**
     * @return int
     */
    public function getNotSentCount()
    {
        return count($this->unknown) + count($this->fails);
    }

    /**
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getWaits()
    {
        return $this->wait;
    }

    /**
     * @return int
     */
    public function getWaitsCount()
    {
        return count($this->wait);
    }

    /**
     * @return int
     */
    public function getDonesCount()
    {
        return $this->doneCount;
    }

    public function getFromDate()
    {
        return $this->fromDate;
    }

    public function getToDate()
    {
        return $this->toDate;
    }
}
