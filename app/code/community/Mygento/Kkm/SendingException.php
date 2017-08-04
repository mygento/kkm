<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_projects
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

class Mygento_Kkm_SendingException extends Exception
{
    const CHEQUE_FAIL = 'The cheque has not been sent to KKM.';
    const ORDER_ID    = 'Order id: ';

    protected $severity;
    protected $extraInfo;
    protected $reason;
    protected $name = 'kkm';

    public function __construct($entity = null, $message = "", $debugData = [], $severity = Zend_Log::ERR) {
        $this->severity = $severity;
        $this->reason = $message;

        //build message here
        $this->buildMessage($entity, $debugData);

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
        return json_encode($this->extraInfo);
    }

    protected function buildMessage($entity, $debugData, $message = "")
    {
        $this->message = $this->getFullTitle();

        if (!$entity) {
            return;
        }

        $incrementId   = $entity::HISTORY_ENTITY_NAME == 'order' ? $entity->getIncrementId() : $entity->getOrder()->getIncrementId();
        $this->message .= ' ' . Mage::helper($this->name)->__(self::ORDER_ID) . $incrementId;
    }
}
