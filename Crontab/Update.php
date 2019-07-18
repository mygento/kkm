<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterfaceFactory;
use Mygento\Kkm\Model\Atol\Response;
use Mygento\Kkm\Model\Processor;

class Update
{
    /**
     * @var UpdateRequestInterfaceFactory
     */
    private $updateRequestFactory;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Transaction\Proxy
     */
    private $transactionHelper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * Update constructor.
     * @param UpdateRequestInterfaceFactory $updateRequestFactory
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        UpdateRequestInterfaceFactory $updateRequestFactory,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->updateRequestFactory = $updateRequestFactory;
        $this->attemptHelper = $attemptHelper;
        $this->kkmHelper = $kkmHelper;
        $this->vendor = $vendor;
        $this->transactionHelper = $transactionHelper;
        $this->publisher = $publisher;
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

        $this->kkmHelper->info('KKM Update statuses Cron START');

        $uuids = $this->transactionHelper->getAllWaitUuids();

        $result = [];
        $i = 0;
        foreach ($uuids as $uuid) {
            try {
                if (!$this->kkmHelper->isMessageQueueEnabled()) {
                    $response = $this->vendor->updateStatus($uuid);

                    $result[] = "UUID {$uuid} new status: {$response->getStatus()}";
                    $i++;
                    continue;
                }

                $this->createUpdateAttempt($uuid);
                $result[] = "UUID {$uuid} update queued";
                $i++;
            } catch (\Exception $e) {
                $this->kkmHelper->critical($e);
            }
        }

        $this->kkmHelper->debug('Update result: ', $result);
        $this->kkmHelper->info("{$i} transactions updated");
        $this->kkmHelper->info('KKM Update statuses Cron END');
    }

    private function createUpdateAttempt($uuid)
    {
        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid, Response::STATUS_WAIT);
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$uuid}");
        }

        /** @var UpdateRequestInterface $updateRequest */
        $updateRequest = $this->updateRequestFactory->create();
        $updateRequest->setUuid($uuid);

        //Register sending Attempt
        $this->attemptHelper->registerUpdateAttempt($entity, false);

        if ($updateRequest instanceof DataObject) {
            $this->kkmHelper->debug('Publish request: ', $updateRequest->toArray());
        }

        $this->publisher->publish(Processor::TOPIC_NAME_UPDATE, $updateRequest);
    }
}
