<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_SendingException extends Exception
{
    const CHEQUE_FAIL = 'The cheque has not been sent to KKM.';
    const ORDER_ID    = 'Order id: ';
    const EXTRA_INFO  = 'Extra Info: ';

    protected $severity;
    protected $name      = 'kkm';
    protected $extraInfo = [];
    protected $orderId;
    protected $entity;
    protected $reason;

    public function __construct($entity = null, $message = "", $debugData = [], $severity = Zend_Log::ERR)
    {
        $this->severity  = $severity;
        $this->reason    = $message;
        $this->extraInfo = $debugData;
        $this->entity   = $entity;

        //build message here
        $this->buildMessage($entity);

        parent::__construct($this->message, 0, null);
    }

    public function getFailTitle()
    {
        return Mage::helper($this->name)->__(self::CHEQUE_FAIL);
    }

    public function getSeverity()
    {
        return $this->severity;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getReason()
    {
        return Mage::helper($this->name)->__($this->reason);
    }

    public function getFullTitle()
    {
        $reason = $this->getReason();

        return $this->getFailTitle() . ($reason ? ' ' . $reason : '');
    }

    public function getExtraData()
    {
        return $this->extraInfo;
    }

    public function getExtraMessage()
    {
        return $this->extraInfo ? Mage::helper($this->name)->__(self::EXTRA_INFO) . json_encode($this->extraInfo) : '';
    }

    protected function buildMessage($entity)
    {
        $this->message = $this->getFullTitle();

        if (!$entity) {
            return;
        }

        $incrementId   = $entity::HISTORY_ENTITY_NAME == 'order' ? $entity->getIncrementId() : $entity->getOrder()->getIncrementId();
        $this->orderId = $incrementId;

        $this->message .= ' ' . Mage::helper($this->name)->__(self::ORDER_ID) . $incrementId;
    }
}
