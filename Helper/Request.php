<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

/**
 * Class Request
 * @package Mygento\Kkm\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory
     */
    private $queueMessageFactory;

    /**
     * Request constructor.
     * @param Transaction $transactionHelper
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory $queueMessageFactory
     */
    public function __construct(
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory $queueMessageFactory
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->queueMessageFactory = $queueMessageFactory;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByRequest(RequestInterface $request)
    {
        return $this->getEntityByIdAndOperationType($request->getSalesEntityId(), $request->getOperationType());
    }

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByUpdateRequest(UpdateRequestInterface $updateRequest)
    {
        return $this->getEntityByUuid($updateRequest->getUuid());
    }

    /**
     * @param string $uuid
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface
     */
    public function getEntityByUuid(string $uuid)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid);
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$uuid}");
        }

        return $entity;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    public function increaseExternalId(RequestInterface $request): void
    {
        if (preg_match('/^(.*)__(\d+)$/', $request->getExternalId(), $matches)) {
            $request->setExternalId($matches[1] . '__' . ($matches[2] + 1));
        } else {
            $request->setExternalId($request->getExternalId() . '__1');
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return \Mygento\Kkm\Api\Queue\QueueMessageInterface
     */
    public function getQueueMessage($request)
    {
        /** @var \Mygento\Kkm\Api\Queue\QueueMessageInterface $message */
        $message = $this->queueMessageFactory->create();
        $message
            ->setEntityId($request->getSalesEntityId())
            ->setEntityStoreId($request->getEntityStoreId())
            ->setOperationType($request->getOperationType());

        return $message;
    }

    /**
     * @param int|string $entityId
     * @param int|string $operationType
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByIdAndOperationType($entityId, $operationType)
    {
        switch ($operationType) {
            case RequestInterface::SELL_OPERATION_TYPE:
            case RequestInterface::RESELL_REFUND_OPERATION_TYPE:
            case RequestInterface::RESELL_SELL_OPERATION_TYPE:
                return $this->invoiceRepository->get($entityId);
            case RequestInterface::REFUND_OPERATION_TYPE:
                return $this->creditmemoRepository->get($entityId);
            default:
                return $this->orderRepository->get($entityId);
        }
    }
}
