<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;

/**
 * Class Transaction
 * @package Mygento\Kkm\Helper
 */
class TransactionAttempt
{
    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * TransactionAttempt constructor.
     * @param MessageEncoder $messageEncoder
     * @param Request $requestHelper
     * @param Data $kkmHelper
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository
     */
    public function __construct(
        MessageEncoder $messageEncoder,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\Data $kkmHelper,
        TransactionAttemptRepositoryInterface $attemptRepository
    ) {
        $this->messageEncoder = $messageEncoder;
        $this->requestHelper = $requestHelper;
        $this->kkmHelper = $kkmHelper;
        $this->attemptRepository = $attemptRepository;
    }

    /**
     * Returns trials number of sending this request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param int $operationType
     * @return int|null
     */
    public function getTrials($entity, int $operationType): ?int
    {
        $attempt = $this->attemptRepository
            ->getByEntityId($operationType, $entity->getEntityId());

        if (!$attempt->getId()) {
            // поддержка старых попыток, которые имеют entity_id=0
            $attempt = $this->attemptRepository
                ->getByIncrementId($operationType, $entity->getOrderId(), $entity->getIncrementId());
        }

        return $attempt->getNumberOfTrials();
    }

    /**
     * Create new attempt based on request
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function registerAttempt(RequestInterface $request, $entity): TransactionAttemptInterface
    {
        $attempt = $this->getAttemptByRequest($request, $entity);

        if ($attempt->getId()) {
            $this->kkmHelper->debug('Attempt found: ' . $attempt->getId(), $attempt->getData());
        }

        $numberOfTrials = $attempt->getNumberOfTrials() === null ? 0 : $attempt->getNumberOfTrials() + 1;
        $totalNumberOfTrials = $attempt->getTotalNumberOfTrials() === null ? 0 : $attempt->getTotalNumberOfTrials() + 1;

        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_NEW)
            ->setOperation($request->getOperationType())
            ->setOrderId($entity->getOrderId())
            ->setSalesEntityId($entity->getEntityId())
            ->setSalesEntityIncrementId($entity->getIncrementId())
            ->setNumberOfTrials($numberOfTrials)
            ->setTotalNumberOfTrials($totalNumberOfTrials);

        return $this->attemptRepository->save($attempt);
    }

    /**
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetNumberOfTrials(RequestInterface $request, $entity): void
    {
        $attempt = $this->getAttemptByRequest($request, $entity);
        $attempt->setNumberOfTrials(0);

        if ($attempt->getId()) {
            $this->attemptRepository->save($attempt);
        }
    }

    /**
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function decreaseByOneTrial(RequestInterface $request, $entity): TransactionAttemptInterface
    {
        $attempt = $this->getAttemptByRequest($request, $entity);

        $trials = $attempt->getNumberOfTrials();
        $maxTrials = $this->kkmHelper->getMaxTrials($entity->getStoreId());

        if ($trials >= $maxTrials) {
            $attempt->setNumberOfTrials($maxTrials - 1);

            return $this->attemptRepository->save($attempt);
        }

        return $attempt;
    }

    /**
     * @param RequestInterface $request
     * @param string $topic
     * @param string|null $scheduledAt
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function scheduleNextAttempt(
        RequestInterface $request,
        string $topic,
        string $scheduledAt = null
    ): TransactionAttemptInterface {
        /** @var CreditmemoInterface|InvoiceInterface|OrderInterface $entity */
        $entity = $this->requestHelper->getEntityByRequest($request);

        $attempt = $this->getAttemptByRequest($request, $entity);
        if (!$attempt->getId()) {
            $attempt
                ->setStatus(TransactionAttemptInterface::STATUS_NEW)
                ->setOperation($request->getOperationType())
                ->setOrderId($entity->getOrderId())
                ->setSalesEntityId($entity->getEntityId())
                ->setSalesEntityIncrementId($entity->getIncrementId())
                ->setNumberOfTrials(0);
        }

        $attempt
            ->setIsScheduled(true)
            ->setScheduledAt($scheduledAt ?? $this->resolveScheduledAt($attempt, $request->getStoreId()))
            ->setRequestJson($this->messageEncoder->encode($topic, $request));

        return $this->attemptRepository->save($attempt);
    }

    /**
     * Create new attempt based on request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param TransactionInterface $transaction
     * @param bool $increaseTrials
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function registerUpdateAttempt(
        $entity,
        TransactionInterface $transaction,
        bool $increaseTrials = true
    ): TransactionAttemptInterface {
        $attempt = $this->attemptRepository
            ->getByEntityId(UpdateRequestInterface::UPDATE_OPERATION_TYPE, $entity->getEntityId());

        $this->kkmHelper->debug('Attempt found: ' . $attempt->getId(), $attempt->getData());

        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_NEW)
            ->setOperation(UpdateRequestInterface::UPDATE_OPERATION_TYPE)
            ->setOrderId($entity->getOrderId())
            ->setTxnType($transaction->getTxnType())
            ->setSalesEntityId($entity->getEntityId())
            ->setSalesEntityIncrementId($entity->getIncrementId())
            ->setNumberOfTrials(
                $increaseTrials
                ? $attempt->getNumberOfTrials() + 1
                : $attempt->getNumberOfTrials()
            )
            ->setTotalNumberOfTrials(
                $increaseTrials
                    ? $attempt->getTotalNumberOfTrials() + 1
                    : $attempt->getTotalNumberOfTrials()
            );

        return $this->attemptRepository->save($attempt);
    }

    /**
     * Mark attempt as Finish
     * @param TransactionAttemptInterface $attempt
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function finishAttempt(TransactionAttemptInterface $attempt): TransactionAttemptInterface
    {
        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_SENT)
            ->setMessage('');

        return $this->attemptRepository->save($attempt);
    }

    /**
     * Mark attempt as Failed
     * @param TransactionAttemptInterface $attempt
     * @param string $message
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function failAttempt(TransactionAttemptInterface $attempt, string $message = ''): TransactionAttemptInterface
    {
        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_ERROR)
            ->setMessage($message);

        return $this->attemptRepository->save($attempt);
    }

    /**
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $entity
     * @return TransactionAttemptInterface
     */
    private function getAttemptByRequest(RequestInterface $request, $entity): TransactionAttemptInterface
    {
        return $this->getAttemptByOperationType($request->getOperationType(), $entity);
    }

    /**
     * @param string $operationType
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $entity
     * @return TransactionAttemptInterface
     */
    private function getAttemptByOperationType(string $operationType, $entity): TransactionAttemptInterface
    {
        $attempt = $this->attemptRepository
            ->getByEntityId($operationType, $entity->getEntityId());

        if (!$attempt->getId()) {
            // поддержка старых попыток, которые имеют entity_id=0
            $attempt = $this->attemptRepository
                ->getByIncrementId($operationType, $entity->getOrderId(), $entity->getIncrementId());
        }

        //Set Parent Attempt (for resell attempts)
        $resellOperations = [
            RequestInterface::RESELL_REFUND_OPERATION_TYPE,
            RequestInterface::RESELL_SELL_OPERATION_TYPE,
        ];
        if (in_array($operationType, $resellOperations, true)) {
            $parentAttempt = $this->attemptRepository->getParentAttempt($entity->getId());
            $attempt->setParentId($parentAttempt->getId());
        }

        return $attempt;
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @param int|string|null $storeId
     * @return string
     */
    private function resolveScheduledAt(TransactionAttemptInterface $attempt, $storeId): string
    {
        $numberOfTrials = $attempt->getNumberOfTrials();

        $scheduledAt = new \DateTime();

        $customRetryIntervals = $this->kkmHelper->getCustomRetryIntervals($storeId);
        if ($customRetryIntervals && isset($customRetryIntervals[$numberOfTrials])) {
            $scheduledAt->modify("+{$customRetryIntervals[$numberOfTrials]} minute");
        }

        return $scheduledAt->format('Y-m-d H:i:s');
    }
}
