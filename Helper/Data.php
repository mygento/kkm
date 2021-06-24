<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
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
     * @param null $scopeCode
     * @return string
     */
    public function getConfig($param, $scopeCode = null)
    {
        return parent::getConfig($this->getCode() . '/' . $param, $scopeCode);
    }

    /**
     * @param int|string|null $storeId
     * @throws \Exception
     * @return string
     */
    public function getAtolLogin($storeId = null)
    {
        $login = $this->getConfig('atol/login', $storeId);

        if (!$login) {
            throw new Exception('No login specified.');
        }

        return $login;
    }

    /**
     * @param int|string|null $storeId
     * @throws \Exception
     * @return string
     */
    public function getAtolPassword($storeId = null)
    {
        $passwd = $this->getConfig('atol/password', $storeId);

        if (!$passwd) {
            throw new Exception('No password specified.');
        }

        return $passwd;
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getStoreEmail($storeId = null)
    {
        return parent::getConfig('trans_email/ident_general/email', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isTestMode($storeId = null): bool
    {
        return (bool) $this->getConfig('atol/test_mode', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isMessageQueueEnabled($storeId = null): bool
    {
        return (bool) $this->getConfig('general/async_enabled', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return false|string
     */
    public function getOrderStatusAfterKkmTransactionDone($storeId = null)
    {
        return $this->getConfig('general/order_status_after_kkm_transaction_done', $storeId) ?: false;
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isRetrySendingEndlessly($storeId = null): bool
    {
        return (bool) $this->getConfig('general/is_retry_sending_endlessly', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isUseCustomRetryIntervals($storeId = null): bool
    {
        return (bool) $this->getConfig('general/is_use_custom_retry_intervals', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return array
     */
    public function getCustomRetryIntervals($storeId = null): array
    {
        $customRetryIntervals = $this->getConfig('general/retry_intervals', $storeId);

        return trim($customRetryIntervals)
            ? array_filter(array_map('trim', explode(',', $customRetryIntervals)))
            : [];
    }

    /**
     * @param int|string|null $storeId
     * @return int
     */
    public function getMaxTrials($storeId = null): int
    {
        return (int) $this->getConfig('general/max_trials', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return int
     */
    public function getMaxUpdateTrials($storeId = null): int
    {
        return (int) $this->getConfig('general/max_update_trials', $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isMarkingEnabled($storeId = null): bool
    {
        return $this->getConfig('marking/enabled', $storeId)
            && $this->getMarkingShouldField($storeId)
            && $this->getMarkingField($storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getMarkingShouldField($storeId = null): string
    {
        return $this->getConfig('marking/marking_status_field', $storeId) ?: '';
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getMarkingField($storeId = null): string
    {
        return $this->getConfig('marking/marking_mark_field', $storeId) ?: '';
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getMarkingRefundField($storeId = null): string
    {
        return $this->getConfig('marking/marking_refund_field', $storeId) ?: '';
    }
}
