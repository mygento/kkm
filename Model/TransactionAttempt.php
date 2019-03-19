<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;
use Mygento\Kkm\Helper\Transaction;

/**
 * Class TransactionAttempt should implement TransactionInterface to
 * provide opportunity to count TransactionAttempt in Kkm statistics and Reports.
 * @package Mygento\Kkm\Model
 */
class TransactionAttempt extends AbstractModel implements TransactionAttemptInterface
{
    const NONE_UUID = 'none';

    /**
     * Get id
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get order id
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set order id
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get operation
     * @return int|null
     */
    public function getOperation()
    {
        return $this->getData(self::OPERATION);
    }

    /**
     * Set operation
     * @param int $operation
     * @return $this
     */
    public function setOperation($operation)
    {
        return $this->setData(self::OPERATION, $operation);
    }

    /**
     * Get sales entity increment id
     * @return int|null
     */
    public function getSalesEntityIncrementId()
    {
        return $this->getData(self::SALES_ENTITY_INCREMENT_ID);
    }

    /**
     * Set sales entity increment id
     * @param int $salesEntityId
     * @return $this
     */
    public function setSalesEntityIncrementId($salesEntityId)
    {
        return $this->setData(self::SALES_ENTITY_INCREMENT_ID, $salesEntityId);
    }

    /**
     * Get status
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getStatusLabel()
    {
        switch ($this->getStatus()) {
            case self::STATUS_NEW:
                return self::STATUS_NEW_LABEL;
            case self::STATUS_SENT:
                return self::STATUS_SENT_LABEL;
            case self::STATUS_ERROR:
                return self::STATUS_ERROR_LABEL;
        }
    }

    /**
     * Get message
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set message
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get number of trials
     * @return int|null
     */
    public function getNumberOfTrials()
    {
        return $this->getData(self::NUMBER_OF_TRIALS);
    }

    /**
     * Set number of trials
     * @param int $numberOfTrials
     * @return $this
     */
    public function setNumberOfTrials($numberOfTrials)
    {
        return $this->setData(self::NUMBER_OF_TRIALS, $numberOfTrials);
    }

    /**
     * Get created at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated at
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId()
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function setTransactionId($id)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParentId()
    {
    }

    /**
     * @inheritDoc
     */
    public function getPaymentId()
    {
    }

    /**
     * @inheritDoc
     */
    public function getTxnId()
    {
        return self::NONE_UUID;
    }

    /**
     * @inheritDoc
     */
    public function getParentTxnId()
    {
    }

    /**
     * @inheritDoc
     */
    public function getTxnType()
    {
        return $this->getOperation();
    }

    /**
     * @inheritDoc
     */
    public function getIsClosed()
    {
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalInformation($key = null)
    {
        $additionalInfo = $this->getData('additional_information');
        if ($additionalInfo && isset($additionalInfo[$key])) {
            return $additionalInfo[$key];
        }

        $additional[Transaction::INCREMENT_ID_KEY] = $this->getSalesEntityIncrementId();
        $additional[Transaction::ERROR_MESSAGE_KEY] = $this->getMessage();

        $this->setData('additional_information', [TransactionEntity::RAW_DETAILS => $additional]);

        return $this->getData('additional_information')[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getChildTransactions()
    {
    }

    /**
     * @inheritDoc
     */
    public function setParentId($id)
    {
    }

    /**
     * @inheritDoc
     */
    public function setPaymentId($id)
    {
    }

    /**
     * @inheritDoc
     */
    public function setTxnId($id)
    {
    }

    /**
     * @inheritDoc
     */
    public function setParentTxnId($id)
    {
    }

    /**
     * @inheritDoc
     */
    public function setTxnType($txnType)
    {
        $this->setData(self::OPERATION, $txnType);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setIsClosed($isClosed)
    {
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalInformation($key, $value)
    {
        $this->setData('additional_information', [$key => $value]);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(\Magento\Sales\Api\Data\TransactionExtensionInterface $extensionAttributes)
    {
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Mygento\Kkm\Model\ResourceModel\TransactionAttempt::class);
    }
}
