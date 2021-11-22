<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Exception;

class Data extends \Mygento\Base\Helper\Data
{
    public const CONFIG_CODE = 'mygento_kkm';

    public const CFG_ATTRIBUTE_VALUE = 'attribute_value';
    public const CFG_JUR_TYPE = 'jur_type';

    private const CONFIG_PATH_TEST_MODE = 'general/test_mode';

    private const CONFIG_PATH_TEST_CLIENT_CERT = 'checkonline/test_cert';
    private const CONFIG_PATH_PROD_CLIENT_CERT = 'checkonline/cert';
    private const CONFIG_PATH_TEST_CLIENT_PRIVATE_KEY = 'checkonline/test_private_key';
    private const CONFIG_PATH_PROD_CLIENT_PRIVATE_KEY = 'checkonline/private_key';
    private const CONFIG_PATH_TEST_API_URL = 'checkonline/test_api_url';
    private const CONFIG_PATH_PROD_API_URL = 'checkonline/api_url';

    /** @var string */
    protected $code = self::CONFIG_CODE;

    /**
     * @var array
     */
    private $statusUpdatableVendorCodes;

    public function __construct(
        \Mygento\Base\Model\LogManager $logManager,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\App\Helper\Context $context,
        $statusUpdatableVendorCodes = []
    ) {
        $this->statusUpdatableVendorCodes = $statusUpdatableVendorCodes;

        parent::__construct($logManager, $encryptor, $context);
    }

    /**
     * @param string $param
     * @param int|string|null $scopeCode
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
        return (bool) $this->getConfig(self::CONFIG_PATH_TEST_MODE, $storeId);
    }

    /**
     * @return array
     */
    public function getAtolNonFatalErrorCodes()
    {
        $atolNonFatalErrorCodes = $this->getConfig('atol/error_codes/non_fatal_error_codes');

        return trim($atolNonFatalErrorCodes)
            ? array_filter(array_map('trim', explode(',', $atolNonFatalErrorCodes)))
            : [];
    }

    /**
     * @param string $errorCode
     * @param string $errorType
     */
    public function isAtolNonFatalError($errorCode, $errorType)
    {
        $atolNonFatalErrors = $this->getAtolNonFatalErrorCodes();

        return in_array($errorType . '_' . $errorCode, $atolNonFatalErrors);
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
     * @return string
     */
    public function getOrderStatusAfterKkmFail()
    {
        return $this->getConfig('general/fail_status');
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

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getCurrentVendorCode($storeId = null)
    {
        return $this->getConfig('general/service', $storeId);
    }

    /**
     * @param string|null $storeId
     */
    public function isVendorNeedUpdateStatus($storeId = null): bool
    {
        $currentVendorCode = $this->getCurrentVendorCode($storeId);

        return in_array($currentVendorCode, $this->statusUpdatableVendorCodes);
    }

    /**
     * @param string|null $storeId
     * @return string|null
     */
    public function getClientCertFileName(?string $storeId = null): ?string
    {
        if ($this->isTestMode($storeId)) {
            return $this->getConfig(self::CONFIG_PATH_TEST_CLIENT_CERT, $storeId);
        }

        return $this->getConfig(self::CONFIG_PATH_PROD_CLIENT_CERT, $storeId);
    }

    /**
     * @param string|null $storeId
     * @return string|null
     */
    public function getClientPrivateKeyFileName(?string $storeId = null): ?string
    {
        if ($this->isTestMode($storeId)) {
            return $this->getConfig(self::CONFIG_PATH_TEST_CLIENT_PRIVATE_KEY, $storeId);
        }

        return $this->getConfig(self::CONFIG_PATH_PROD_CLIENT_PRIVATE_KEY, $storeId);
    }

    public function getCheckonlineApiUrl(?string $storeId = null): ?string
    {
        if ($this->isTestMode($storeId)) {
            return $this->getConfig(self::CONFIG_PATH_TEST_API_URL, $storeId);
        }

        return $this->getConfig(self::CONFIG_PATH_PROD_API_URL, $storeId);
    }
}
