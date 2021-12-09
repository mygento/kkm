<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use DateTime;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Mygento\Base\Model\Payment\Transaction as TransactionBase;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var Data
     */
    private $kkmHelper;

    /**
     * @var TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param MessageEncoder $messageEncoder
     * @param Request $requestHelper
     * @param Data $kkmHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionAttemptRepositoryInterface $attemptRepository
     */
    public function __construct(
        MessageEncoder $messageEncoder,
        Request $requestHelper,
        Data $kkmHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionAttemptRepositoryInterface $attemptRepository
    ) {
        $this->messageEncoder = $messageEncoder;
        $this->requestHelper = $requestHelper;
        $this->kkmHelper = $kkmHelper;
        $this->attemptRepository = $attemptRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
     * @throws LocalizedException
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
            ->setStoreId($entity->getStoreId())
            ->setSalesEntityId($entity->getEntityId())
            ->setSalesEntityIncrementId($entity->getIncrementId())
            ->setNumberOfTrials($numberOfTrials)
            ->setTotalNumberOfTrials($totalNumberOfTrials)
            ->setErrorCode(null)
            ->setErrorType(null);

        return $this->attemptRepository->save($attempt);
    }

    /**
     * @param RequestInterface $request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws LocalizedException
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
     * @throws LocalizedException
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
     * @throws NoSuchEntityException
     * @throws LocalizedException
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
                ->setStoreId($entity->getStoreId())
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
     * @throws LocalizedException
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
            ->setStoreId($entity->getStoreId())
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
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param TransactionInterface $transaction
     * @throws LocalizedException
     */
    public function updateStatusByTransaction($entity, TransactionInterface $transaction)
    {
        $operationType = $this->resolveAttemptOperationType($entity, $transaction);
        $attempt = $this->getAttemptByOperationType(
            $operationType,
            $entity
        );

        if (!$attempt->getId()) {
            return;
        }

        $attemptStatus = $transaction->getIsClosed() && $transaction->getKkmStatus() == ResponseInterface::STATUS_DONE
            ? TransactionAttemptInterface::STATUS_DONE
            : TransactionAttemptInterface::STATUS_SENT;

        $this->updateStatus($attempt, $attemptStatus);
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @param int $status
     * @param string $message
     * @throws LocalizedException
     * @return TransactionAttemptInterface
     */
    public function updateStatus(
        TransactionAttemptInterface $attempt,
        int $status,
        string $message = ''
    ): TransactionAttemptInterface {
        $attempt
            ->setStatus($status)
            ->setMessage($message);

        return $this->attemptRepository->save($attempt);
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @throws LocalizedException
     * @return bool
     */
    public function isResendAvailable(TransactionAttemptInterface $attempt)
    {
        $successfulAttemptsSearchResult = $this->attemptRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(TransactionAttemptInterface::ORDER_ID, $attempt->getOrderId())
                ->addFilter(TransactionAttemptInterface::OPERATION, $attempt->getOperation())
                ->addFilter(TransactionAttemptInterface::SALES_ENTITY_ID, $attempt->getSalesEntityId())
                ->addFilter(
                    TransactionAttemptInterface::STATUS,
                    TransactionAttemptInterface::STATUS_ERROR,
                    'neq'
                )->create()
        );

        return $successfulAttemptsSearchResult->getTotalCount() == 0;
    }

    /**
     * @param TransactionAttemptInterface $attempt
     * @return string
     */
    public function getEntityType(TransactionAttemptInterface $attempt)
    {
        $operationType = $attempt->getOperation();

        switch ($operationType) {
            case RequestInterface::SELL_OPERATION_TYPE:
            case RequestInterface::RESELL_REFUND_OPERATION_TYPE:
            case RequestInterface::RESELL_SELL_OPERATION_TYPE:
                return 'invoice';
            case RequestInterface::REFUND_OPERATION_TYPE:
                return 'creditmemo';
            default:
                return 'order';
        }
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param TransactionInterface $transaction
     * @return int
     */
    public function resolveAttemptOperationType($entity, TransactionInterface $transaction): int
    {
        if ($entity instanceof CreditmemoInterface) {
            return RequestInterface::REFUND_OPERATION_TYPE;
        }

        if ($transaction->getTxnType() == TransactionBase::TYPE_FISCAL_REFUND) {
            return RequestInterface::RESELL_REFUND_OPERATION_TYPE;
        }

        if ($transaction->getParentId()) {
            return RequestInterface::RESELL_SELL_OPERATION_TYPE;
        }

        return RequestInterface::SELL_OPERATION_TYPE;
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

        $scheduledAt = new DateTime();

        $customRetryIntervals = $this->kkmHelper->getCustomRetryIntervals($storeId);
        if ($customRetryIntervals && isset($customRetryIntervals[$numberOfTrials])) {
            $scheduledAt->modify("+{$customRetryIntervals[$numberOfTrials]} minute");
        }

        return $scheduledAt->format('Y-m-d H:i:s');
    }
}
