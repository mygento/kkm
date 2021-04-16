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
     * @throws Exception
     * @return string
     */
    public function getAtolLogin()
    {
        $login = $this->getConfig('atol/login');

        if ($login == false) {
            throw new Exception('No login specified.');
        }

        return (string) $login;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getAtolPassword()
    {
        $passwd = $this->getConfig('atol/password');

        if ($passwd == false) {
            throw new Exception('No password specified.');
        }

        return (string) $passwd;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getStoreEmail($storeId = null)
    {
        return parent::getConfig('trans_email/ident_general/email', $storeId);
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return (bool) $this->getConfig('atol/test_mode');
    }

    /**
     * @return bool
     */
    public function isMessageQueueEnabled($storeId = null)
    {
        return (bool) $this->getConfig('general/async_enabled', $storeId);
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
    public function isUseCustomRetryIntervals($storeId = null)
    {
        return (bool) $this->getConfig('general/is_use_custom_retry_intervals', $storeId);
    }

    /**
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
     * @return int
     */
    public function getMaxUpdateTrials()
    {
        return (int) $this->getConfig('general/max_update_trials');
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isMarkingEnabled($storeId = null): bool
    {
        return $this->getConfig('marking/enabled', $storeId)
            && $this->getMarkingShouldField($storeId)
            && $this->getMarkingField($storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMarkingShouldField($storeId = null)
    {
        return $this->getConfig('marking/marking_status_field', $storeId) ?: '';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMarkingField($storeId = null)
    {
        return $this->getConfig('marking/marking_mark_field', $storeId) ?: '';
    }

    /**
     * @param int|null $storeId
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
    public function isCheckonlineTestMode($storeId = null)
    {
        return $this->getConfig('checkonline/test_mode', $storeId);
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
     * @return \Mygento\Kkm\Model\VendorInterface
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function getCurrentVendor($storeId = null)
    {
        return $this->vendorFactory->create($this->getCurrentVendorCode($storeId));
    }
}
