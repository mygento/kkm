<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Update constructor.
     * @param UpdateRequestInterfaceFactory $updateRequestFactory
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        UpdateRequestInterfaceFactory $updateRequestFactory,
        \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->updateRequestFactory = $updateRequestFactory;
        $this->attemptHelper = $attemptHelper;
        $this->kkmHelper = $kkmHelper;
        $this->transactionHelper = $transactionHelper;
        $this->updateProcessor = $updateProcessor;
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

    /**
     * @param int|string $storeId
     * @throws \Exception
     */
    private function proceed($storeId)
    {
        //Проверка включения Cron и необходимость выполнять операцию обновления статуса для вендора
        if (!$this->kkmHelper->getConfig('general/update_cron', $storeId)
            || !$this->kkmHelper->isVendorNeedUpdateStatus($storeId)
        ) {
            return;
        }

        $this->kkmHelper->info('KKM Update statuses Cron START');

        $uuids = $this->transactionHelper->getAllWaitUuids($storeId);

        $result = [];
        $i = 0;
        foreach ($uuids as $uuid) {
            try {
                if (!$this->kkmHelper->isMessageQueueEnabled($storeId)) {
                    $response = $this->updateProcessor->proceedSync($uuid);

                    $result[] = "UUID {$uuid} new status: {$response->getStatus()}";
                    $i++;
                    continue;
                }

                $this->createUpdateAttempt($uuid, $storeId);
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

    /**
     * @param string $uuid
     * @param int|string $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createUpdateAttempt($uuid, $storeId)
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
        $updateRequest
            ->setUuid($uuid)
            ->setEntityStoreId($storeId);

        //Register sending Attempt
        $this->attemptHelper->registerUpdateAttempt($entity, $transaction, false);

        if ($updateRequest instanceof DataObject) {
            $this->kkmHelper->debug('Publish request: ', $updateRequest->toArray());
        }

        $this->updateProcessor->proceedAsync($updateRequest);
    }
}
