<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Exception;
use Mygento\Kkm\Model\VendorFactory;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data
{
    const CONFIG_CODE = 'mygento_kkm';

    const CFG_ATTRIBUTE_VALUE = 'attribute_value';
    const CFG_JUR_TYPE = 'jur_type';

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
     * @var VendorFactory
     */
    private $vendorFactory;

    public function __construct(
        \Mygento\Base\Model\LogManager $logManager,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Kkm\Model\VendorFactory $vendorFactory
    ) {
        $this->vendorFactory = $vendorFactory;

        parent::__construct($logManager, $encryptor, $context);
    }

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
     * @param string|null $storeId
     * @throws Exception
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
     * @param string|null $storeId
     * @throws Exception
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
     * @param string|null $storeId
     * @return string|null
     */
    public function getStoreEmail($storeId = null)
    {
        return parent::getConfig('trans_email/ident_general/email', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isTestMode(?string $storeId = null): bool
    {
        return (bool) $this->getConfig(self::CONFIG_PATH_TEST_MODE, $storeId);
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isMessageQueueEnabled($storeId = null)
    {
        return (bool) $this->getConfig('general/async_enabled', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getOrderStatusAfterKkmTransactionDone($storeId = null)
    {
        return $this->getConfig('general/order_status_after_kkm_transaction_done', $storeId) ?: false;
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isRetrySendingEndlessly($storeId = null)
    {
        return (bool) $this->getConfig('general/is_retry_sending_endlessly', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isUseCustomRetryIntervals($storeId = null)
    {
        return (bool) $this->getConfig('general/is_use_custom_retry_intervals', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return array
     */
    public function getCustomRetryIntervals($storeId = null)
    {
        $customRetryIntervals = $this->getConfig('general/retry_intervals', $storeId);

        return trim($customRetryIntervals)
            ? array_filter(array_map('trim', explode(',', $customRetryIntervals)))
            : [];
    }

    /**
     * @param string|null $storeId
     * @return int
     */
    public function getMaxTrials($storeId = null)
    {
        return (int) $this->getConfig('general/max_trials', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return int
     */
    public function getMaxUpdateTrials($storeId = null)
    {
        return (int) $this->getConfig('general/max_update_trials', $storeId);
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isMarkingEnabled($storeId = null): bool
    {
        return $this->getConfig('marking/enabled', $storeId)
            && $this->getMarkingShouldField($storeId)
            && $this->getMarkingField($storeId);
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getMarkingShouldField($storeId = null)
    {
        return $this->getConfig('marking/marking_status_field', $storeId) ?: '';
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getMarkingField($storeId = null)
    {
        return $this->getConfig('marking/marking_mark_field', $storeId) ?: '';
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getMarkingRefundField($storeId = null)
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
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     * @return \Mygento\Kkm\Model\VendorInterface
     */
    public function getCurrentVendor($storeId = null)
    {
        return $this->vendorFactory->create($this->getCurrentVendorCode($storeId));
    }

    /**
     * @param string|null $storeId
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     * @return bool
     */
    public function isVendorNeedUpdateStatus($storeId = null)
    {
        $vendor = $this->getCurrentVendor($storeId);
        return $vendor instanceof \Mygento\Kkm\Model\StatusUpdatable;
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
