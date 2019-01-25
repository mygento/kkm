<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2019 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Statistics extends Mage_Core_Model_Abstract
{
    protected $fails     = [];
    protected $unknown     = [];
    protected $waitCount = 0;
    protected $doneCount = 0;
    protected $fromDate;
    protected $toDate;

    /**
     * @param Mygento_Kkm_Model_Status $status
     */
    public function addFail($status)
    {
        $this->fails[] = $status;
    }

    /**
     * @param Mygento_Kkm_Model_Status $status
     */
    public function addUnknown($status)
    {
        $this->unknown[] = $status;
    }

    public function addWait()
    {
        $this->waitCount++;
    }

    public function addDone()
    {
        $this->doneCount++;
    }

    public function setPeriod($from, $to = null)
    {
        if (!$to) {
            $currentTime = Mage::getModel('core/date')->timestamp(time());
            $date        = new Zend_Date($currentTime);
            $to          = $date->toString('Y-MM-dd H:mm:s');
        }

        $this->fromDate = $from;
        $this->toDate   = $to;
    }

    public function getFails()
    {
        return $this->fails;
    }

    public function getFailsCount()
    {
        return count($this->fails);
    }

    public function getWaitsCount()
    {
        return $this->waitCount;
    }

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
