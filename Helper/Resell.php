<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Base\Model\Payment\Transaction as TransactionBase;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\TransactionAttemptRepositoryInterface;
use Mygento\Kkm\Model\Atol\Response;

class Resell
{
    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface
     */
    private $attemptRepository;

    /**
     * @param \Mygento\Kkm\Helper\Transaction $transactionHelper
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $attemptRepository
     */
    public function __construct(
        Transaction $transactionHelper,
        TransactionAttemptRepositoryInterface $attemptRepository
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->attemptRepository = $attemptRepository;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param ResponseInterface $response
     * @return bool
     */
    public function isNeededToResendSell($entity, $response): bool
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($response->getUuid());

        $children = $transaction->getChildTransactions();
        foreach ($children as $child) {
            if ($child->getTxnType() === TransactionBase::TYPE_FISCAL) {
                return false;
            }
        }

        return
            $response->getStatus() === ResponseInterface::STATUS_DONE
            && $entity->getEntityType() === 'invoice'
            && $transaction->getTxnType() === TransactionBase::TYPE_FISCAL_REFUND;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool
     */
    public function isResellFailed($invoice): bool
    {
        /** @var \Mygento\Base\Model\Payment\Transaction $lastRefundTxn */
        $lastRefundTxn = $this->transactionHelper->getLastResellRefundTransaction($invoice);
        $isWait = $lastRefundTxn->getKkmStatus() === Response::STATUS_WAIT;
        if (!$lastRefundTxn->getId() || $isWait) {
            return false;
        }

        if ($lastRefundTxn->getKkmStatus() === Response::STATUS_FAIL) {
            return true;
        }

        if (!$lastRefundTxn->hasChildTransaction()) {
            return true;
        }

        $children = $lastRefundTxn->getChildTransactions();
        foreach ($children as $transaction) {
            if ($transaction->getTxnType() !== TransactionBase::TYPE_FISCAL) {
                continue;
            }

            $isDone = $transaction->getKkmStatus() === Response::STATUS_DONE;
            $isWait = $transaction->getKkmStatus() === Response::STATUS_WAIT;

            if ($isDone || $isWait) {
                return false;
            }
        }

        return true;
    }
}
