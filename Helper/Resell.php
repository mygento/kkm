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

class Resell
{
    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    public function __construct(
        Transaction $transactionHelper
    ) {
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param ResponseInterface $response
     * @return bool
     */
    public function isNeededToResendSell($entity, $response): bool
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($response->getUuid());

        return
            $response->getStatus() === ResponseInterface::STATUS_DONE
            && $entity->getEntityType() === 'invoice'
            && $transaction->getTxnType() === TransactionBase::TYPE_FISCAL_REFUND;
    }
}
