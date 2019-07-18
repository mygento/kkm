<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Exception;

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
     * @return string|null
     */
    public function getStoreEmail()
    {
        return parent::getConfig('trans_email/ident_general/email');
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
    public function isMessageQueueEnabled()
    {
        return (bool) $this->getConfig('general/async_enabled');
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
}
