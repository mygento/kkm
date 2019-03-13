<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Model\Atol\Response;

/**
 * Class Transaction
 * @package Mygento\Kkm\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionAttempt
{

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;
    /**
     * @var \Mygento\Kkm\Api\Data\TransactionAttemptInterfaceFactory
     */
    private $attemptFactory;
    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Api\Data\TransactionAttemptInterfaceFactory $attemptFactory,
        \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->attemptFactory = $attemptFactory;
        $this->attemptRepository = $attemptRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function registerAttempt(RequestInterface $request, $entityIncrementId, $orderId)
    {
        //TODO: Exists attempt check it?
        $attempt = $this->attemptRepository
            ->getByEntityId($request->getOperationType(), $request->getSalesEntityId());

        if (!$attempt->getId()) {
            $attempt = $this->attemptFactory->create();
        }
        $trials  = $attempt->getNumberOfTrials();

        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_NEW)
            ->setOperation($request->getOperationType())
            ->setOrderId($orderId)
            ->setSalesEntityIncrementId($entityIncrementId)
            ->setNumberOfTrials(is_null($trials) ? 0 : $trials + 1)
            ;

        return $this->attemptRepository->save($attempt);
    }

    public function finishAttempt(TransactionAttemptInterface $attempt)
    {
        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_SENT)
            ->setMessage('');

        return $this->attemptRepository->save($attempt);
    }

    public function failAttempt(TransactionAttemptInterface $attempt,$message = ''){
        $attempt
            ->setStatus(TransactionAttemptInterface::STATUS_ERROR)
            ->setMessage($message)
        ;

        return $this->attemptRepository->save($attempt);
    }



}
