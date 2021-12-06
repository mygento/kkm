<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Resend\Validator;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Exception\ResendAvailabilityException;
use Mygento\Kkm\Helper\Transaction;
use Mygento\Kkm\Model\Atol\Response;
use Mygento\Kkm\Model\Resend\ValidatorInterface;

class TransactionStatus implements ValidatorInterface
{
    /**
     * @var Transaction
     */
    private $transactionHelper;

    /**
     * @param Transaction $transactionHelper
     */
    public function __construct(Transaction $transactionHelper)
    {
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @throws ResendAvailabilityException
     */
    public function validate($entity)
    {
        $transactions = $this->transactionHelper->getTransactionsByEntity($entity);

        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();

            if ($status === Response::STATUS_DONE || $status === Response::STATUS_WAIT) {
                throw new ResendAvailabilityException(
                    __('Entity with id "%1" has a transaction with "wait" or "done" status', $entity->getIncrementId())
                );
            }
        }

        return true;
    }
}
