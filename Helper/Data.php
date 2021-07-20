<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Exception;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data
{
    public const CONFIG_CODE = 'mygento_kkm';

    public const CFG_ATTRIBUTE_VALUE = 'attribute_value';
    public const CFG_JUR_TYPE = 'jur_type';

    /** @var string */
    protected $code = self::CONFIG_CODE;

    /**
     * @param string $param
     * @param string|null $scopeCode
     * @return string
     */
    public function getConfig($param, $scopeCode = null)
    {
        return parent::getConfig($this->getCode() . '/' . $param, $scopeCode);
    }

    /**
     * @param int|null $storeId
     * @throws \Exception
     * @return string
     */
    public function getAtolLogin($storeId = null)
    {
        $login = $this->getConfig('atol/login', $storeId);

        if ($login == false) {
            throw new Exception('No login specified.');
        }

        return (string) $login;
    }

    /**
     * @param int|null $storeId
     * @throws \Exception
     * @return string
     */
    public function getAtolPassword($storeId = null)
    {
        $passwd = $this->getConfig('atol/password', $storeId);

        if ($passwd == false) {
            throw new Exception('No password specified.');
        }

        return (string) $passwd;
    }

    /**
     * @return string|null
     */
    public function getStoreEmail()
    {
        return parent::getConfig('trans_email/ident_general/email');
    }

    /**
     * @return bool
     */
    public function isTestMode($storeId = null)
    {
        return (bool) $this->getConfig('atol/test_mode', $storeId);
    }

    /**
     * @return bool
     */
    public function isMessageQueueEnabled()
    {
        return (bool) $this->getConfig('general/async_enabled');
    }

    /**
     * @return string
     */
    public function getOrderStatusAfterKkmTransactionDone()
    {
        return $this->getConfig('general/order_status_after_kkm_transaction_done') ?: false;
    }

    /**
     * @return bool
     */
    public function isRetrySendingEndlessly()
    {
        return (bool) $this->getConfig('general/is_retry_sending_endlessly');
    }

    /**
     * @return bool
     */
    public function isUseCustomRetryIntervals()
    {
        return (bool) $this->getConfig('general/is_use_custom_retry_intervals');
    }

    /**
     * @return array
     */
    public function getCustomRetryIntervals()
    {
        $customRetryIntervals = $this->getConfig('general/retry_intervals');

        return trim($customRetryIntervals)
            ? array_filter(array_map('trim', explode(',', $customRetryIntervals)))
            : [];
    }

    /**
     * @return int
     */
    public function getMaxTrials()
    {
        return (int) $this->getConfig('general/max_trials');
    }

    /**
     * @return int
     */
    public function getMaxUpdateTrials()
    {
        return (int) $this->getConfig('general/max_update_trials');
    }

    /**
     * @return bool
     */
    public function isMarkingEnabled(): bool
    {
        return $this->getConfig('marking/enabled')
            && $this->getMarkingShouldField()
            && $this->getMarkingField();
    }

    /**
     * @return string
     */
    public function getMarkingShouldField()
    {
        return $this->getConfig('marking/marking_status_field') ?: '';
    }

    /**
     * @return string
     */
    public function getMarkingField()
    {
        return $this->getConfig('marking/marking_mark_field') ?: '';
    }

    /**
     * @return string
     */
    public function getMarkingRefundField()
    {
        return $this->getConfig('marking/marking_refund_field') ?: '';
    }
}
