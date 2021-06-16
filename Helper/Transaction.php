<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\EntityInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection as TransactionCollection;
use Magento\Store\Api\StoreRepositoryInterface;
use Mygento\Base\Model\Payment\Transaction as TransactionBase;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Model\Atol\Response;

/**
 * Class Transaction
 * @package Mygento\Kkm\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Transaction
{
    public const ENTITY_KEY = 'entity';
    public const INCREMENT_ID_KEY = 'increment_id';
    public const UUID_KEY = 'uuid';
    public const STATUS_KEY = 'status';
    public const ERROR_MESSAGE_KEY = 'error';
    public const RAW_RESPONSE_KEY = 'raw_response';
    public const FPD_KEY = 'fiscal_document_attribute';

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepo;

    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $kkmHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var InvoiceCollectionFactory
     */
    private $invoiceCollectionFactory;

    /**
     * @var CreditmemoCollectionFactory
     */
    private $creditmemoCollectionFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Transaction constructor.
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param CreditmemoCollectionFactory $creditmemoCollectionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param Data $kkmHelper
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        CreditmemoCollectionFactory $creditmemoCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->transactionRepo = $transactionRepo;
        $this->transactionFactory = $transactionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
        $this->kkmHelper = $kkmHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param ResponseInterface $response
     * @param RequestInterface|null $request
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function registerTransaction($entity, ResponseInterface $response, RequestInterface $request = null)
    {
        $isResellRefund = $request && $request->getOperationType() === RequestInterface::RESELL_REFUND_OPERATION_TYPE;
        $isResellSell = $request && $request->getOperationType() === RequestInterface::RESELL_SELL_OPERATION_TYPE;

        if ($isResellRefund) {
            return $this->saveResellRefundTransaction($entity, $response);
        }

        if ($isResellSell) {
            return $this->saveResellSellTransaction($entity, $response);
        }

        if ($entity instanceof InvoiceInterface) {
            return $this->saveSellTransaction($entity, $response, $request);
        }

        return $this->saveRefundTransaction($entity, $response, $request);
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function saveSellTransaction(
        InvoiceInterface $invoice,
        ResponseInterface $response,
        RequestInterface $request = null
    ) {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Invoice %2',
                $response->getUuid(),
                $invoice->getIncrementId()
            )
        );
        $type = TransactionBase::TYPE_FISCAL;

        return $this->saveTransaction($invoice, $response, $type);
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param ResponseInterface $response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function saveResellRefundTransaction(InvoiceInterface $invoice, ResponseInterface $response)
    {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Resell (refund) Invoice %2',
                $response->getUuid(),
                $invoice->getIncrementId()
            )
        );
        $type = TransactionBase::TYPE_FISCAL_REFUND;

        $doneTransaction = $this->getDoneTransaction($invoice, true);

        return $this->saveTransaction($invoice, $response, $type, $doneTransaction);
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param ResponseInterface $response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function saveResellSellTransaction(InvoiceInterface $invoice, ResponseInterface $response)
    {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Resell (sell) Invoice %2',
                $response->getUuid(),
                $invoice->getIncrementId()
            )
        );
        $type = TransactionBase::TYPE_FISCAL;

        $parentTransaction = $this->getDoneTransaction($invoice, true);

        return $this->saveTransaction($invoice, $response, $type, $parentTransaction);
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $includingResell
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function getDoneTransaction($invoice, $includingResell = false): TransactionInterface
    {
        //Transactions are sorted by createdAt by default.
        $transactions = $this->getTransactionsByInvoice($invoice, $includingResell);

        if (!$transactions) {
            throw new LocalizedException(__('Invoice %1 has no KKM transactions.', $invoice->getIncrementId()));
        }

        /** @var \Magento\Sales\Api\Data\TransactionInterface $doneTransaction */
        $doneTransaction = $this->transactionFactory->create();
        array_walk(
            $transactions,
            function ($transaction) use (&$doneTransaction) {
                if ($doneTransaction->getId()) {
                    return;
                }

                $doneTransaction = $transaction->getKkmStatus() === Response::STATUS_DONE
                    ? $transaction
                    : $doneTransaction;
            }
        );

        return $doneTransaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function getLastResellRefundTransaction($invoice): TransactionInterface
    {
        //Transactions are sorted by createdAt by default.
        $transactions = $this->getTransactionsByInvoice($invoice, true);

        if (!$transactions) {
            throw new LocalizedException(__('Invoice %1 has no KKM transactions.', $invoice->getIncrementId()));
        }

        /** @var \Magento\Sales\Api\Data\TransactionInterface $refundTransaction */
        $refundTransaction = $this->transactionFactory->create();
        array_walk(
            $transactions,
            function ($transaction) use (&$refundTransaction) {
                if ($refundTransaction->getId()) {
                    return;
                }

                /** @var TransactionInterface $transaction */
                $refundTransaction =
                    $transaction->getTxnType() === TransactionBase::TYPE_FISCAL_REFUND
                        ? $transaction
                        : $refundTransaction;
            }
        );

        return $refundTransaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function getLastResellSellTransaction($invoice): TransactionInterface
    {
        //Transactions are sorted by createdAt by default.
        $transactions = $this->getTransactionsByInvoice($invoice, true);

        if (!$transactions) {
            throw new LocalizedException(__('Invoice %1 has no KKM transactions.', $invoice->getIncrementId()));
        }

        /** @var \Magento\Sales\Api\Data\TransactionInterface $sellTransaction */
        $sellTransaction = $this->transactionFactory->create();
        array_walk(
            $transactions,
            function ($transaction) use (&$sellTransaction) {
                if ($sellTransaction->getId()) {
                    return;
                }

                /** @var TransactionInterface $transaction */
                $sellTransaction =
                    $transaction->getTxnType() === TransactionBase::TYPE_FISCAL && $transaction->getParentId()
                        ? $transaction
                        : $sellTransaction;
            }
        );

        return $sellTransaction;
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function saveRefundTransaction(
        CreditmemoInterface $creditmemo,
        ResponseInterface $response,
        RequestInterface $request = null
    ) {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Creditmemo %2',
                $response->getUuid(),
                $creditmemo->getIncrementId()
            )
        );
        $type = TransactionBase::TYPE_FISCAL_REFUND;

        return $this->saveTransaction($creditmemo, $response, $type);
    }

    /**
     * @param int $transactionId
     * @param int $paymentId
     * @param int $orderId
     * @return bool
     */
    public function isTransactionExists($transactionId, $paymentId, $orderId)
    {
        return $transactionId && $this->transactionRepo->getByTransactionId(
            $transactionId,
            $paymentId,
            $orderId
        );
    }

    /**
     * @param InvoiceInterface $invoice
     * @return bool
     */
    public function isResellOpened(InvoiceInterface $invoice): bool
    {
        $transactions = $this->getTransactionsByInvoice($invoice, true);

        foreach ($transactions as $transaction) {
            if ($transaction->getTxnType() !== TransactionBase::TYPE_FISCAL_REFUND) {
                continue;
            }

            if ($transaction->getKkmStatus() === Response::STATUS_WAIT) {
                return true;
            }

            $children = $transaction->getChildTransactions();
            foreach ($children as $child) {
                if ($child->getTxnType() !== TransactionBase::TYPE_FISCAL) {
                    continue;
                }

                if ($child->getKkmStatus() === Response::STATUS_WAIT) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param bool $includingResell
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByInvoice(InvoiceInterface $invoice, $includingResell = false)
    {
        return $this->getTransactionsByEntity($invoice, $includingResell);
    }

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByCreditmemo(CreditmemoInterface $creditmemo)
    {
        return $this->getTransactionsByEntity($creditmemo);
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param bool $includingResellTransactions
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByEntity($entity, $includingResellTransactions = false)
    {
        /** @var Order $order */
        $order = $entity->getOrder();
        $this->searchCriteriaBuilder->addFilter('order_id', $order->getId());

        //Fetch the freshest entity
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection('DESC')
            ->create();

        $this->searchCriteriaBuilder->setSortOrders([$sortOrder]);

        if ($entity->getEntityType() === 'invoice') {
            $types = [
                TransactionBase::TYPE_FISCAL_PREPAYMENT,
                TransactionBase::TYPE_FISCAL,
            ];

            if ($includingResellTransactions) {
                $types[] = TransactionBase::TYPE_FISCAL_REFUND;
            }

            $this->searchCriteriaBuilder->addFilter(
                'txn_type',
                $types,
                'in'
            );
        } else {
            $this->searchCriteriaBuilder->addFilter(
                'txn_type',
                TransactionBase::TYPE_FISCAL_REFUND
            );
        }

        $transactions = $this->transactionRepo->getList($this->searchCriteriaBuilder->create());

        //Order has several creditmemos or invoices
        foreach ($transactions->getItems() as $index => $item) {
            $data = $item->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            $invalidIncrementId = $data[self::INCREMENT_ID_KEY] !== $entity->getIncrementId();
            $invalidEntityType = $data[self::ENTITY_KEY] !== $entity->getEntityType();

            if ($invalidIncrementId || $invalidEntityType) {
                $transactions->removeItemByKey($index);
            }
        }

        return $transactions->getItems();
    }

    /**
     * @param string $txnId
     * @param string $kkmStatus
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function getTransactionByTxnId($txnId, $kkmStatus = null)
    {
        $this->searchCriteriaBuilder->addFilter(TransactionInterface::TXN_ID, $txnId);
        if ($kkmStatus) {
            $this->searchCriteriaBuilder->addFilter('kkm_status', $kkmStatus);
        }

        /** @var TransactionCollection $transactions */
        $transactions = $this->transactionRepo->getList($this->searchCriteriaBuilder->create());

        return $transactions->getFirstItem();
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface
     */
    public function getEntityByTransaction(TransactionInterface $transaction)
    {
        $data = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
        $entityType = $data[self::ENTITY_KEY];
        $incrementId = $data[self::INCREMENT_ID_KEY];

        switch ($entityType) {
            case 'invoice':
                /** @var InvoiceCollection $invoiceCollection */
                $invoiceCollection = $this->invoiceCollectionFactory->create();

                return $invoiceCollection
                    ->addFieldToFilter('order_id', $transaction->getOrderId())
                    ->addFieldToFilter('increment_id', $incrementId)
                    ->getFirstItem();
            case 'creditmemo':
                /** @var CreditmemoCollection $creditmemoCollection */
                $creditmemoCollection = $this->creditmemoCollectionFactory->create();

                return $creditmemoCollection
                    ->addFieldToFilter('order_id', $transaction->getOrderId())
                    ->addFieldToFilter('increment_id', $incrementId)
                    ->getFirstItem();
            default:
                throw new \Exception("Unknown entity type {$entityType}");
        }
    }

    /**
     * Returns UUID if invoice or creditmemo has uncompleted kkm transactions
     * @param CreditmemoInterface|InvoiceInterface $entity Invoice|Creditmemo
     * @return string[] uuid
     */
    public function getWaitUuid($entity): array
    {
        $transactions = $this->getTransactionsByEntity($entity, true);
        $uuids = [];
        foreach ($transactions as $transaction) {
            if ($transaction->getKkmStatus() === Response::STATUS_WAIT) {
                $uuids[] = $transaction->getTxnId();
            }
        }

        return $uuids;
    }

    /**
     * @param int|string|null $storeId
     * @return string[]
     */
    public function getAllWaitUuids($storeId = null): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('kkm_status', Response::STATUS_WAIT)
            ->create();

        /** @var TransactionCollection $transactions */
        $transactions = $this->transactionRepo->getList($searchCriteria);

        if ($this->kkmHelper->isMessageQueueEnabled($storeId)) {
            // если используются очереди, получаем только те транзации, для которых нет активных
            // заданий на обновление статуса
            $alias = 't_mygento_kkm_transaction_attempt';
            $conditions[] = sprintf(
                'main_table.%s = %s.%s',
                TransactionInterface::ORDER_ID,
                $alias,
                TransactionAttemptInterface::ORDER_ID
            );
            $conditions[] = sprintf(
                'main_table.%s = %s.%s',
                TransactionInterface::TXN_TYPE,
                $alias,
                TransactionAttemptInterface::TXN_TYPE
            );
            $conditions[] = sprintf(
                '%s.%s = %s',
                $alias,
                TransactionAttemptInterface::OPERATION,
                UpdateRequestInterface::UPDATE_OPERATION_TYPE
            );
            $conditions[] = sprintf(
                '%s.%s > %s',
                $alias,
                TransactionAttemptInterface::UPDATED_AT,
                $transactions->getConnection()->quote((new \DateTime('-1 hour'))->format(Mysql::TIMESTAMP_FORMAT))
            );
            $transactions->getSelect()
                ->joinLeft(
                    [$alias => $transactions->getTable('mygento_kkm_transaction_attempt')],
                    implode(' AND ', $conditions),
                    []
                )
                ->joinLeft(
                    'sales_order',
                    sprintf(
                        'main_table.%s = sales_order.%s',
                        TransactionInterface::ORDER_ID,
                        OrderInterface::ENTITY_ID
                    ),
                    []
                )
                ->where(sprintf(
                    '%s.%s IS NULL',
                    $alias,
                    TransactionAttemptInterface::ID
                ))
                ->where(sprintf(
                    'sales_order.%s = %s',
                    OrderInterface::ENTITY_ID,
                    $storeId
                ))
                ->group(TransactionInterface::TXN_ID);
        } else {
            $transactions->getSelect()
                ->joinLeft(
                    'sales_order',
                    sprintf(
                        'main_table.%s = sales_order.%s',
                        TransactionInterface::ORDER_ID,
                        OrderInterface::ENTITY_ID
                    ),
                    []
                )
                ->where(sprintf(
                    'sales_order.%s = %s',
                    OrderInterface::STORE_ID,
                    $storeId
                ))->group(TransactionInterface::TXN_ID);
        }

        return $transactions->getColumnValues(TransactionInterface::TXN_ID);
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @throws \InvalidArgumentException
     * @return string|null
     */
    public function getExternalId(TransactionInterface $transaction): ?string
    {
        $additionalInformation = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);

        $externalId = $additionalInformation[RequestInterface::EXTERNAL_ID_KEY] ?? '';

        if ($externalId) {
            return $externalId;
        }

        $rawResponse = $additionalInformation
            ? $this->jsonSerializer->unserialize($additionalInformation[self::RAW_RESPONSE_KEY])
            : [];

        return $rawResponse[RequestInterface::EXTERNAL_ID_KEY] ?? null;
    }

    /**
     * Returns "Фискальный признак документа" if it exists.
     *
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @return string
     */
    public function getFpd(TransactionInterface $transaction): string
    {
        $additionalInformation = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);

        return $additionalInformation[self::FPD_KEY] ?? '';
    }

    /**
     * @param CreditmemoInterface|EntityInterface|InvoiceInterface $entity
     * @param ResponseInterface $response
     * @param string $type
     * @param TransactionInterface|null $parentTransaction
     * @return TransactionInterface
     */
    protected function saveTransaction($entity, ResponseInterface $response, $type, $parentTransaction = null)
    {
        $txnId = $response->getUuid();
        $order = $entity->getOrder();
        $payment = $entity->getOrder()->getPayment();
        $isClosed = ($response->isDone() || $response->isFailed()) ? 1 : 0;
        $rawResponse = json_encode(json_decode((string) $response), JSON_UNESCAPED_UNICODE);
        $additional = [
            self::ENTITY_KEY => $entity->getEntityType(),
            self::INCREMENT_ID_KEY => $entity->getIncrementId(),
            RequestInterface::EXTERNAL_ID_KEY => $response->getExternalId(),
            self::UUID_KEY => $txnId,
            self::STATUS_KEY => $response->getStatus(),
            self::ERROR_MESSAGE_KEY => $response->getErrorMessage(),
            self::RAW_RESPONSE_KEY => $rawResponse,
        ];
        $additional = array_merge($additional, (array) $response->getPayload());

        //Update
        if ($this->isTransactionExists($txnId, $payment->getId(), $order->getId())) {
            $transaction = $this->updateTransactionData(
                $txnId,
                $payment->getId(),
                $order->getId(),
                $additional
            );
            $transaction
                ->setIsClosed($isClosed)
                ->setKkmStatus($response->getStatus());

            return $this->transactionRepo->save($transaction);
        }

        //Create
        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionFactory->create()
            ->setPayment($payment)
            ->setOrder($order)
            ->setFailSafe(true)
            ->setTxnType($type)
            ->setIsClosed($isClosed)
            ->setTxnId($txnId)
            ->setKkmStatus($response->getStatus())
            ->setKkmStatus($response->getStatus())
            ->setAdditionalInformation(
                TransactionEntity::RAW_DETAILS,
                $additional
            );

        if ($parentTransaction) {
            $transaction
                ->setParentId($parentTransaction->getTransactionId())
                ->setParentTxnId($parentTransaction->getTxnId());
        }

        return $this->transactionRepo->save($transaction);
    }

    /**
     * @param int $transactionId
     * @param int $paymentId
     * @param int $orderId
     * @param array $transData
     * @return mixed
     */
    protected function updateTransactionData($transactionId, $paymentId, $orderId, $transData)
    {
        $this->kkmHelper->info('update transaction: ' . $transactionId);
        $transaction = $this->transactionRepo->getByTransactionId(
            $transactionId,
            $paymentId,
            $orderId
        );

        $transaction->setAdditionalInformation(
            TransactionEntity::RAW_DETAILS,
            $transData
        );

        return $transaction;
    }
}
