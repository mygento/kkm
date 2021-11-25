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
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Api\Queue\QueueMessageInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Helper\Data;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProceedScheduledAttempt
{
    /**
     * @var TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /** @var Data */
    private $kkmHelper;

    /**
     * @var PublisherInterface
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param TransactionAttemptRepositoryInterface $attemptRepository
     * @param Data $kkmHelper
     * @param PublisherInterface $publisher
     * @param MessageEncoder $messageEncoder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TransactionAttemptRepositoryInterface $attemptRepository,
        Data $kkmHelper,
        PublisherInterface $publisher,
        MessageEncoder $messageEncoder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->attemptRepository = $attemptRepository;
        $this->kkmHelper = $kkmHelper;
        $this->publisher = $publisher;
        $this->messageEncoder = $messageEncoder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->proceed($store->getId());
        }
    }

    private function proceed($storeId)
    {
        //Проверка включения Cron
        if (!$this->kkmHelper->getConfig('general/update_cron', $storeId)) {
            return;
        }

        $attempts = $this->attemptRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(TransactionAttemptInterface::IS_SCHEDULED, true)
                ->addFilter(TransactionAttemptInterface::SCHEDULED_AT, $this->dateTime->gmtDate(), 'lteq')
                ->addFilter(TransactionAttemptInterface::STORE_ID, $storeId)
                ->setPageSize($this->kkmHelper->getConfig('general/retry_limit', $storeId))
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
     * @throws LocalizedException
     */
    private function publishRequest(TransactionAttemptInterface $attempt)
    {
        $topic = $this->getTopic($attempt);
        /** @var QueueMessageInterface $message */
        $message = $this->messageEncoder->decode($topic, $attempt->getRequestJson());
        $this->publisher->publish($topic, $message);
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
                return SendInterface::TOPIC_NAME_SELL;
            case RequestInterface::REFUND_OPERATION_TYPE:
                return SendInterface::TOPIC_NAME_REFUND;
            case UpdateRequestInterface::UPDATE_OPERATION_TYPE:
                return UpdateInterface::TOPIC_NAME_UPDATE;
            default:
                throw new LocalizedException(__('Unsupported operation: %1', $attempt->getOperation()));
        }
    }
}
