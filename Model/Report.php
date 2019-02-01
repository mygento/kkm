<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Mygento\Kkm\Model\Atol\Response;
use Mygento\Kkm\Helper\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;


class Report
{
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    private $transactionRepo;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    private $timezone;
    /**
     * @var \Mygento\Kkm\Model\StatisticsFactory
     */
    private $statisticsFactory;

    public function __construct(
        \Mygento\Kkm\Model\StatisticsFactory $statisticsFactory,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    ) {
        $this->transactionRepo = $transactionRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezone = $timezone;
        $this->statisticsFactory = $statisticsFactory;
    }

    /**
     * @return \Mygento\Kkm\Model\Statistics
     */
    public function getTodayStatistics()
    {
        $from = $this->timezone->date()->format('Y-m-d 00:00:00');

        return $this->getStatisticsByPeriod($from);
    }

    /**
     * @return \Mygento\Kkm\Model\Statistics
     * @throws \Exception
     */
    public function getYesterdayStatistics()
    {
        $dateTime = new \DateTime('yesterday');

        $from = $this->timezone->date($dateTime)->format('Y-m-d 00:00:00');
        $to = $this->timezone->date($dateTime)->format('Y-m-d 23:59:59');

        return $this->getStatisticsByPeriod($from, $to);
    }

    /**
     * @return \Mygento\Kkm\Model\Statistics
     * @throws \Exception
     */
    public function getWeekStatistics()
    {
        $dateTime = new \DateTime('monday this week');
        $from = $this->timezone->date($dateTime)->format('Y-m-d 00:00:00');

        return $this->getStatisticsByPeriod($from);
    }

    /**
     * @param $from
     * @param null $to
     * @return \Mygento\Kkm\Model\Statistics
     */
    public function getStatisticsByPeriod($from, $to = null)
    {
        if  ($to) {
            $this->searchCriteriaBuilder->addFilter('created_at', $to, 'lteq');
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('created_at', $from, 'gteq')
            ->create();

        return $this->collectStatistics($searchCriteria)
            ->setFromDate($from)
            ->setToDate($to);
    }

    /**
     * @param $searchCriteria
     * @return \Mygento\Kkm\Model\Statistics
     */
    private function collectStatistics($searchCriteria)
    {
        $transactions = $this->transactionRepo->getList($searchCriteria);
        $statistics = $this->statisticsFactory->create();

        foreach ($transactions->getItems() as $item) {
            $info   = $item->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            $status = $info[Transaction::STATUS_KEY] ?? 'unknown';

            switch ($status) {
                case Response::STATUS_DONE:
                    $statistics->addDone($item);
                    break;
                case Response::STATUS_WAIT:
                    $statistics->addWait($item);
                    break;
                case Response::STATUS_FAIL:
                    $statistics->addFail($item);
                    break;
                default:
                    $statistics->addUnknown($item);
                    break;
            }
        }

        return $statistics;
    }
}