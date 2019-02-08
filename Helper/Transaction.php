<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Model\Atol\Response;

/**
 * Class Transaction
 * @package Mygento\Kkm\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Transaction
{
    const ENTITY_KEY        = 'entity';
    const INCREMENT_ID_KEY  = 'increment_id';
    const UUID_KEY          = 'uuid';
    const STATUS_KEY        = 'status';
    const ERROR_MESSAGE_KEY = 'error';
    const RAW_RESPONSE_KEY  = 'raw_response';

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
    private $kkmHelper;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Model\Order\CreditmemoRepository
     */
    private $creditmemoRepo;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo
     */
    private $creditmemoResource;
    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceFactory;

    public function __construct(
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepo,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\CreditmemoRepository $creditmemoRepo,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo $creditmemoResource,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
        $this->transactionRepo       = $transactionRepo;
        $this->transactionFactory    = $transactionFactory;
        $this->kkmHelper             = $kkmHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->creditmemoRepo        = $creditmemoRepo;
        $this->creditmemoResource    = $creditmemoResource;
        $this->invoiceFactory        = $invoiceFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param \Mygento\Kkm\Model\Atol\Response $response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function saveSellTransaction(Invoice $invoice, Response $response)
    {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Invoice %2',
                $response->getUuid(),
                $invoice->getIncrementId()
            )
        );
        $type = \Mygento\Base\Model\Payment\Transaction::TYPE_FISCAL;

        return $this->saveTransaction($invoice, $response, $type);
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param \Mygento\Kkm\Model\Atol\Response $response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function saveRefundTransaction(Creditmemo $creditmemo, Response $response)
    {
        $this->kkmHelper->info(
            __(
                'start save transaction %1. Creditmemo %2',
                $response->getUuid(),
                $creditmemo->getIncrementId()
            )
        );
        $type = \Mygento\Base\Model\Payment\Transaction::TYPE_FISCAL_REFUND;

        return $this->saveTransaction($creditmemo, $response, $type);
    }

    /**
     * @param \Magento\Sales\Api\Data\EntityInterface $entity
     * @param \Mygento\Kkm\Model\Atol\Response $response
     * @param mixed $type
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    protected function saveTransaction($entity, Response $response, $type)
    {
        $txnId       = $response->getUuid();
        $order       = $entity->getOrder();
        $payment     = $entity->getOrder()->getPayment();
        $isClosed    = ($response->isDone() || $response->isFailed()) ? 1 : 0;
        $rawResponse = json_encode(json_decode((string)$response), JSON_UNESCAPED_UNICODE);
        $additional  = [
            self::ENTITY_KEY        => $entity->getEntityType(),
            self::INCREMENT_ID_KEY  => $entity->getIncrementId(),
            self::UUID_KEY          => $txnId,
            self::STATUS_KEY        => $response->getStatus(),
            self::ERROR_MESSAGE_KEY => $response->getErrorMessage(),
            self::RAW_RESPONSE_KEY  => $rawResponse,
        ];
        $additional  = array_merge($additional, (array)$response->getPayload());

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
        $transaction = $this->transactionFactory->create()
            ->setPayment($payment)
            ->setOrder($order)
            ->setFailSafe(true)
            ->setTxnType($type)
            ->setIsClosed($isClosed)
            ->setTxnId($txnId)
            ->setKkmStatus($response->getStatus())
            ->setAdditionalInformation(
                TransactionEntity::RAW_DETAILS,
                $additional
            );

        return $this->transactionRepo->save($transaction);
    }

    public function isTransactionExists($transactionId, $paymentId, $orderId)
    {
        return $transactionId && $this->transactionRepo->getByTransactionId(
            $transactionId,
            $paymentId,
            $orderId
        );
    }

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

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByInvoice($invoice)
    {
        return $this->getTransactionsByEntity($invoice);
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByCreditmemo(Creditmemo $creditmemo)
    {
        return $this->getTransactionsByEntity($creditmemo);
    }

    /**
     * @param $entity
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
     */
    public function getTransactionsByEntity($entity)
    {
        $order = $entity->getOrder();
        $type = $entity->getEntityType();
        $txnType = $type === 'invoice'
            ? \Mygento\Base\Model\Payment\Transaction::TYPE_FISCAL
            : \Mygento\Base\Model\Payment\Transaction::TYPE_FISCAL_REFUND;

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $order->getId())
            ->addFilter('txn_type', $txnType)
            ->create();

        $transactions = $this->transactionRepo->getList($searchCriteria);

        //Order has several creditmemos
        foreach ($transactions->getItems() as $index => $item) {
            $data = $item->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            if ($data[self::INCREMENT_ID_KEY] !== $entity->getIncrementId()) {
                $transactions->removeItemByKey($index);
            }
        }

        return $transactions->getItems();
    }

    /**
     * @param string $txnId
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function getTransactionByTxnId($txnId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('txn_id', $txnId)
            ->create();

        $transactions = $this->transactionRepo->getList($searchCriteria);

        return $transactions->getFirstItem();
    }

    /**
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @throws \Exception
     * @return \Magento\Sales\Api\Data\CreditmemoInterface|\Magento\Sales\Model\Order\Invoice
     */
    public function getEntityByTransaction(TransactionInterface $transaction)
    {
        $data = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
        $entityType = $data[self::ENTITY_KEY];
        $incrementId = $data[self::INCREMENT_ID_KEY];

        switch ($entityType) {
            case 'invoice':
                $invoice = $this->invoiceFactory->create()->loadByIncrementId($incrementId);

                return $invoice;
            case 'creditmemo':
                $creditmemo = $this->creditmemoRepo->create();
                $this->creditmemoResource->load($creditmemo, $incrementId, 'increment_id');

                return $creditmemo;
            default:
                throw new \Exception("Unknown entity type {$entityType}");
        }
    }

    /** Returns UUID if invoice or creditmemo has uncompleted kkm transactions
     * @param \Magento\Sales\Model\EntityInterface $entity Invoice|Creditmemo
     * @return null|string uuid
     */
    public function getWaitUuid($entity)
    {
        $transactions = $this->getTransactionsByEntity($entity);
        foreach ($transactions as $transaction) {
            if ($transaction->getKkmStatus() === Response::STATUS_WAIT) {
                return $transaction->getTxnId();
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getAllWaitUuids()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('kkm_status', Response::STATUS_WAIT)
            ->create();

        $transactions = $this->transactionRepo->getList($searchCriteria);

        return $transactions->getColumnValues('txn_id');
    }
}
