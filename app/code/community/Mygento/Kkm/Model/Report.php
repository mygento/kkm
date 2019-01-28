<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2019 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Report
{
    public function sendReport()
    {
        $helper = Mage::helper('kkm');
        $period = $helper->getConfig('general/report_period');

        switch ($period) {
            case Mygento_Kkm_Model_Source_Periods::WEEKLY_PERIOD:
                $statistics = $this->getWeekStatistics();
                break;
            case Mygento_Kkm_Model_Source_Periods::DAILY_PERIOD:
                $statistics = $this->getTodayStatistics();
                break;
            case Mygento_Kkm_Model_Source_Periods::DAILY_PREV_PERIOD:
            default:
                $statistics = $this->getYesterdayStatistics();
                break;
        }

        return $this->send($statistics);
    }

    /**
     * @return \Mygento_Kkm_Model_Statistics
     * @throws \Zend_Date_Exception
     */
    public function getYesterdayStatistics()
    {
        $currentTime = Mage::getModel('core/date')->timestamp(time());
        $date        = new Zend_Date($currentTime);
        $date->subDay(1);

        $fromDate = $date->toString('Y-MM-dd 00:00:00');
        $toDate   = $date->toString('Y-MM-dd 23:59:59');

        $statuses   = $this->getStatusesByPeriod($fromDate, $toDate);
        $statistics = $this->collectStatistics($statuses);
        $statistics->setPeriod($fromDate, $toDate);

        return $statistics;
    }

    /**
     * @return \Mygento_Kkm_Model_Statistics
     * @throws \Zend_Date_Exception
     */
    public function getWeekStatistics()
    {
        $currentTime = Mage::getModel('core/date')->timestamp(time());
        $monday      = strtotime('monday this week', $currentTime);
        $date        = new Zend_Date($monday);

        $fromDate = $date->toString('Y-MM-dd 00:00:00');

        $statuses   = $this->getStatusesByPeriod($fromDate);
        $statistics = $this->collectStatistics($statuses);
        $statistics->setPeriod($fromDate);

        return $statistics;
    }

    /**
     * @return \Mygento_Kkm_Model_Statistics
     * @throws \Zend_Date_Exception
     */
    public function getTodayStatistics()
    {
        $currentTime = Mage::getModel('core/date')->timestamp(time());
        $date        = new Zend_Date($currentTime);

        $fromDate = $date->toString('Y-MM-dd 00:00:00');

        $statuses   = $this->getStatusesByPeriod($fromDate);
        $statistics = $this->collectStatistics($statuses);
        $statistics->setPeriod($fromDate);

        return $statistics;
    }

    /**
     * @param \Mygento_Kkm_Model_Resource_Status_Collection $collection
     * @return \Mygento_Kkm_Model_Statistics
     */
    protected function collectStatistics($collection)
    {
        $statistics = Mage::getModel('kkm/statistics');

        foreach ($collection as $item) {
            if ($item->getShortStatus() == 'fail') {
                $statistics->addFail($item);
                continue;
            }
            if ($item->getShortStatus() == 'wait') {
                $statistics->addWait($item);
                continue;
            }
            if ($item->getShortStatus() == 'done') {
                $statistics->addDone($item);
                continue;
            }
            $statistics->addUnknown($item);
        }

        return $statistics;
    }

    /**
     * @param \Mygento_Kkm_Model_Statistics $statistics
     * @return bool
     */
    protected function send($statistics)
    {
        $helper = Mage::helper('kkm');
        if (!$helper->getConfig('general/enabled') || !$helper->getConfig('general/send_report_enabled')) {
            return false;
        }

        $params = [
            'statistics' => $statistics,
        ];

        $emails = $helper->getConfig('report_emails');

        // Loading of template
        $templateId = $helper->getConfig('send_report_template');

        if (is_numeric($templateId)) {
            $emailTemplate = Mage::getModel('core/email_template')->load($templateId);
        } else {
            $emailTemplate = Mage::getModel('core/email_template')->loadDefault($templateId);
        }

        $senderEmail = $helper->getConfig('report_sender_email');
        $senderName  = $helper->getConfig('report_sender_name');

        $emailTemplate->setSenderName($senderName);
        $emailTemplate->setSenderEmail($senderEmail);

        try {
            return $emailTemplate->send($emails, null, $params);
        } catch (Exception $error) {
            $helper->addLog('Send report error: ' . $error->getMessage(), Zend_Log::WARN);

            return false;
        }
    }

    /**
     * @param string $from date
     * @param string|null $to date
     * @return Mygento_Kkm_Model_Resource_Status_Collection
     */
    public function getStatusesByPeriod($from, $to = null)
    {
        $statuses = Mage::getModel('kkm/status')->getCollection()
            ->addFieldToFilter('created_at', ['gteq' => $from]);
        if ($to) {
            $statuses->addFieldToFilter('created_at', ['lteq' => $to]);
        }

        return $statuses;
    }
}
