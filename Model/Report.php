<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Helper\Transaction;
use Mygento\Kkm\Model\Atol\Response;

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

    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * Report constructor.
     * @param StatisticsFactory $statisticsFactory
     * @param TransactionAttemptRepositoryInterface $attemptRepository
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     */
    public function __construct(
        StatisticsFactory $statisticsFactory,
        TransactionAttemptRepositoryInterface $attemptRepository,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    ) {
        $this->transactionRepo = $transactionRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezone = $timezone;
        $this->statisticsFactory = $statisticsFactory;
        $this->attemptRepository = $attemptRepository;
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
     * @throws \Exception
     * @return \Mygento\Kkm\Model\Statistics
     */
    public function getYesterdayStatistics()
    {
        $dateTime = new \DateTime('yesterday');

        $from = $this->timezone->date($dateTime)->format('Y-m-d 00:00:00');
        $to = $this->timezone->date($dateTime)->format('Y-m-d 23:59:59');

        return $this->getStatisticsByPeriod($from, $to);
    }

    /**
     * @throws \Exception
     * @return \Mygento\Kkm\Model\Statistics
     */
    public function getWeekStatistics()
    {
        $dateTime = new \DateTime('monday this week');
        $from = $this->timezone->date($dateTime)->format('Y-m-d 00:00:00');

        return $this->getStatisticsByPeriod($from);
    }

    /**
     * @param string $from
     * @param string|null $to
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mygento\Kkm\Model\Statistics
     */
    public function getStatisticsByPeriod($from, $to = null)
    {
        if ($to) {
            $this->searchCriteriaBuilder->addFilter('created_at', $to, 'lteq');
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('created_at', $from, 'gteq');

        return $this->collectStatistics($searchCriteria)
            ->setFromDate($from)
            ->setToDate($to);
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Mygento\Kkm\Model\Statistics
     */
    private function collectStatistics($searchCriteriaBuilder)
    {
        $transactions = $this->transactionRepo->getList(
            $searchCriteriaBuilder
                ->addFilter('kkm_status', null, 'neq')
                ->create()
        );
//        $transactionAttempts = $this->attemptRepository->getList(
//            $searchCriteriaBuilder->create()
//        );

        /** @var $statistics \Mygento\Kkm\Model\Statistics */
        $statistics = $this->statisticsFactory->create();

//        $items = array_merge($transactions->getItems(), $transactionAttempts->getItems());
        $items = $transactions->getItems();
        foreach ($items as $item) {
            $info = $item->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            $status = $item->getKkmStatus()
                ?? ($info[Transaction::STATUS_KEY] ?? 'unknown');

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
