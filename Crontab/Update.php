<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterfaceFactory;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Transaction\Proxy as TransactionHelper;
use Mygento\Kkm\Helper\TransactionAttempt;
use Mygento\Kkm\Model\Atol\Response;

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
     * @var \Mygento\Kkm\Helper\Transaction\Proxy
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\UpdateInterface
     */
    private $updateProcessor;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Update constructor.
     * @param UpdateRequestInterfaceFactory $updateRequestFactory
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        UpdateRequestInterfaceFactory $updateRequestFactory,
        UpdateInterface $updateProcessor,
        TransactionAttempt $attemptHelper,
        Data $kkmHelper,
        TransactionHelper $transactionHelper,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->updateRequestFactory = $updateRequestFactory;
        $this->attemptHelper = $attemptHelper;
        $this->kkmHelper = $kkmHelper;
        $this->transactionHelper = $transactionHelper;
        $this->updateProcessor = $updateProcessor;
        $this->storeRepository = $storeRepository;
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

        $result = [];
        $i = 0;
        foreach ($this->storeRepository->getList() as $store) {
            $uuids = $this->transactionHelper->getAllWaitUuids($store->getId());
            foreach ($uuids as $uuid) {
                try {
                    if (!$this->kkmHelper->isMessageQueueEnabled($store->getId())) {
                        $response = $this->updateProcessor->proceedSync($uuid);

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
        }

        $this->kkmHelper->debug('Update result: ', $result);
        $this->kkmHelper->info("{$i} transactions updated");
        $this->kkmHelper->info('KKM Update statuses Cron END');
    }

    /**
     * @param string $uuid
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function createUpdateAttempt(string $uuid): void
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid, Response::STATUS_WAIT);
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$uuid}");
        }

        $updateRequest = $this->updateRequestFactory->create();
        $updateRequest->setUuid($uuid);

        //Register sending Attempt
        $this->attemptHelper->registerUpdateAttempt($entity, $transaction, false);

        $this->kkmHelper->debug('Publish request: ', $updateRequest->toArray());

        $this->updateProcessor->proceedAsync($updateRequest);
    }
}
