<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Model\Processor;

class ProceedScheduledAttempt
{
    /**
     * @var TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param TransactionAttemptRepositoryInterface $attemptRepository
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param MessageEncoder $messageEncoder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTime $dateTime
     */
    public function __construct(
        TransactionAttemptRepositoryInterface $attemptRepository,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        MessageEncoder $messageEncoder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTime $dateTime
    ) {
        $this->attemptRepository = $attemptRepository;
        $this->kkmHelper = $kkmHelper;
        $this->publisher = $publisher;
        $this->messageEncoder = $messageEncoder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTime = $dateTime;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        //Проверка включения Cron
        if (!$this->kkmHelper->getConfig('general/update_cron')) {
            return;
        }

        $attempts = $this->attemptRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(TransactionAttemptInterface::IS_SCHEDULED, true)
                ->addFilter(TransactionAttemptInterface::SCHEDULED_AT, $this->dateTime->gmtDate(), 'lteq')
                ->create()
        )->getItems();

        /** @var TransactionAttemptInterface $attempt */
        foreach ($attempts as $attempt) {
            try {
                $this->publishRequest($attempt);
                $attempt->setIsScheduled(false);
                $this->attemptRepository->save($attempt);
            } catch (\Exception $e) {
                $this->kkmHelper->critical($e);
            }
        }
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function publishRequest(TransactionAttemptInterface $attempt)
    {
        $topic = $this->getTopic($attempt);
        /** @var RequestInterface $request */
        $request = $this->messageEncoder->decode($topic, $attempt->getRequestJson());
        $this->publisher->publish($topic, $request);
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @throws LocalizedException
     * @return string
     */
    private function getTopic(TransactionAttemptInterface $attempt)
    {
        switch ($attempt->getOperation()) {
            case RequestInterface::SELL_OPERATION_TYPE:
                return Processor::TOPIC_NAME_SELL;
            case RequestInterface::REFUND_OPERATION_TYPE:
                return Processor::TOPIC_NAME_REFUND;
            case UpdateRequestInterface::UPDATE_OPERATION_TYPE:
                return Processor::TOPIC_NAME_UPDATE;
            default:
                throw new LocalizedException(__('Unsupported operation: %1', $attempt->getOperation()));
        }
    }
}
