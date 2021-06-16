<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

use Magento\Framework\Exception\MailException;
use Mygento\Kkm\Model\Source\Period;

class Report
{
    const EMAIL_SUBJECT = 'KKM Report';
    const EMAIL_SENDER_NAME = 'KKM Reporter';

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\Report
     */
    private $report;

    /**
     * @var \Mygento\Kkm\Helper\Email
     */
    private $emailHelper;

    /**
     * Report constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Model\Report $report
     * @param \Mygento\Kkm\Helper\Email $emailHelper
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\Report $report,
        \Mygento\Kkm\Helper\Email $emailHelper
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->report = $report;
        $this->emailHelper = $emailHelper;
    }

    /**
     * Execute
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->kkmHelper->getConfig('report/enabled')) {
            return;
        }
        $this->kkmHelper->info('KKM Report Cron START');

        $senderEmail = $this->kkmHelper->getConfig('report/sender_email');
        $senderName = self::EMAIL_SENDER_NAME;
        $recipient = $this->kkmHelper->getConfig('report/email');
        $template = $this->kkmHelper->getConfig('report/template');
        $period = $this->kkmHelper->getConfig('report/period');

        switch ($period) {
            case Period::WEEKLY_NAME:
                $statistics = $this->report->getWeekStatistics();
                break;
            case Period::TODAY_NAME:
                $statistics = $this->report->getTodayStatistics();
                break;
            case Period::YESTERDAY_NAME:
            default:
                $statistics = $this->report->getYesterdayStatistics();
                break;
        }

        $fields = [
            'subject' => self::EMAIL_SUBJECT,
            'statistics' => $statistics,
        ];

        try {
            $this->emailHelper
                ->setArea(\Magento\Framework\App\Area::AREA_ADMINHTML)
                ->setSender($senderEmail, $senderName)
                ->setRecipient($recipient)
                ->setTemplate($template)
                ->setFields($fields)
                ->send();
        } catch (MailException $e) {
            $this->kkmHelper->error('Report was not sent. ' . $e->getMessage());
        }
        $this->kkmHelper->info('KKM Report Cron END');
    }
}
