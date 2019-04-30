<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data
{
    const CONFIG_CODE = 'mygento_kkm';

    /** @var string */
    protected $code = self::CONFIG_CODE;

    /**
     * @param string $param
     * @return string
     */
    public function getConfig($param)
    {
        return parent::getConfig($this->getCode() . '/' . $param);
    }

    /**
     * @throws \Exception
     * @return string
     */
    public function getAtolLogin()
    {
        $login = $this->getConfig('atol/login');

        if ($login == false) {
            throw new \Exception('No login specified.');
        }

        return (string) $login;
    }

    /**
     * @throws \Exception
     * @return string
     */
    public function getAtolPassword()
    {
        $passwd = $this->getConfig('atol/password');

        if ($passwd == false) {
            throw new \Exception('No password specified.');
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
}
