<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

class Report
{
    const EMAIL_SUBJECT = 'KKM Report';

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

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\Report $report,
        \Mygento\Kkm\Helper\Email $emailHelper
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->report = $report;
        $this->emailHelper = $emailHelper;
    }

    public function execute()
    {
        if (!$this->kkmHelper->getConfig('report/enabled')) {
            return;
        }
        $this->kkmHelper->info('KKM Report Cron START');

        $senderEmail = $this->kkmHelper->getConfig('report/sender_email');
        $senderName = 'KKM Reporter';
        $recipient = $this->kkmHelper->getConfig('report/email');
        $template = $this->kkmHelper->getConfig('report/template');

        //TODO: use Source model to define report period


        $statistics = $this->report->getTodayStatistics();

        $fields = [
            'subject' => self::EMAIL_SUBJECT,
            'statistics' => $statistics
        ];

        $this->emailHelper
            ->setArea(\Magento\Framework\App\Area::AREA_ADMINHTML)
            ->setSender($senderEmail, $senderName)
            ->setRecipient($recipient)
            ->setTemplate($template)
            ->setFields($fields)
            ->send();


//        $this->kkmHelper->debug($result);
//        $this->kkmHelper->info("{$i} transactions updated");
        $this->kkmHelper->info('KKM Report Cron END');
    }
}
