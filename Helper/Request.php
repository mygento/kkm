<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class Request
{
    /**
     * @var Transaction
     */
    private $transactionHelper;

    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Request constructor.
     * @param Transaction $transactionHelper
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByRequest($request)
    {
        switch ($request->getOperationType()) {
            case RequestInterface::SELL_OPERATION_TYPE:
                return $this->invoiceRepository->get($request->getSalesEntityId());
                break;
            case RequestInterface::REFUND_OPERATION_TYPE:
                return $this->creditmemoRepository->get($request->getSalesEntityId());
                break;
            default:
                return $this->orderRepository->get($request->getSalesEntityId());
                break;
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByUpdateRequest($updateRequest)
    {
        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionHelper->getTransactionByTxnId($updateRequest->getUuid());
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$updateRequest->getUuid()}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$updateRequest->getUuid()}");
        }

        return $entity;
    }
}
