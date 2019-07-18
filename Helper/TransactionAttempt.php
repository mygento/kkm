<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

/**
 * Class Transaction
 * @package Mygento\Kkm\Helper
 */
class TransactionAttempt
{
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * TransactionAttempt constructor.
     * @param Data $kkmHelper
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
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
    public function getTrials($entity, $operationType)
    {
        /** @var TransactionAttemptInterface $attempt */
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
    public function registerAttempt(RequestInterface $request, $entity)
    {
        /** @var TransactionAttemptInterface $attempt */
        $attempt = $this->attemptRepository
            ->getByEntityId($request->getOperationType(), $entity->getEntityId());

        if (!$attempt->getId()) {
            // поддержка старых попыток, которые имеют entity_id=0
            $attempt = $this->attemptRepository
                ->getByIncrementId($request->getOperationType(), $entity->getOrderId(), $entity->getIncrementId());
        }

        $this->kkmHelper->debug('Attempt found: ' . $attempt->getId(), $attempt->getData());

        $trials = $attempt->getNumberOfTrials();

        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_NEW)
            ->setOperation($request->getOperationType())
            ->setOrderId($entity->getOrderId())
            ->setSalesEntityId($entity->getEntityId())
            ->setSalesEntityIncrementId($entity->getIncrementId())
            ->setNumberOfTrials($trials === null ? 0 : $trials + 1);

        return $this->attemptRepository->save($attempt);
    }

    /**
     * Create new attempt based on request
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param bool $increaseTrials
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function registerUpdateAttempt($entity, $increaseTrials = true)
    {
        /** @var TransactionAttemptInterface $attempt */
        $attempt = $this->attemptRepository
            ->getByEntityId(UpdateRequestInterface::UPDATE_OPERATION_TYPE, $entity->getEntityId());

        $this->kkmHelper->debug('Attempt found: ' . $attempt->getId(), $attempt->getData());

        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_NEW)
            ->setOperation(UpdateRequestInterface::UPDATE_OPERATION_TYPE)
            ->setOrderId($entity->getOrderId())
            ->setSalesEntityId($entity->getEntityId())
            ->setSalesEntityIncrementId($entity->getIncrementId())
            ->setNumberOfTrials(
                $increaseTrials
                ? $attempt->getNumberOfTrials() + 1
                : $attempt->getNumberOfTrials()
            );

        return $this->attemptRepository->save($attempt);
    }

    /**
     * Mark attempt as Finish
     * @param TransactionAttemptInterface $attempt
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return TransactionAttemptInterface
     */
    public function finishAttempt(TransactionAttemptInterface $attempt)
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
    public function failAttempt(TransactionAttemptInterface $attempt, $message = '')
    {
        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_ERROR)
            ->setMessage($message);

        return $this->attemptRepository->save($attempt);
    }
}
