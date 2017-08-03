<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
trait Mygento_Kkm_Helper_Logger_Db
{

    public function writeLog($message, $severity)
    {
        //This code is from Magento core. See Mage::log() method.
        if (is_array($message) || is_object($message)) {
            // @codingStandardsIgnoreStart
            $message = print_r($message, true);
            // @codingStandardsIgnoreEnd
        }

        $hostname        = gethostname() !== false ? gethostname() : '';
        $message         = '[' . $hostname . '] ' . $message;
        $advancedMessage = $severity <= Zend_Log::CRIT ? $this->getCallstack() : '';

        $this->_getDbLogger()
            ->setModuleCode($this->_code)
            ->setMessage($message)
            ->setTimestamp(date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time())))
            ->setSeverity($severity)
            ->setAdvancedInfo($advancedMessage)
            ->save();
    }

    public function _getDbLogger()
    {
        return Mage::getModel($this->_code . '/log_entry');
    }

    public function getCallstack($delim = "\n")
    {
        $dt = debug_backtrace();
        $cs = '';
        foreach ($dt as $t) {
            $cs .= $t['file'] . ' line ' . $t['line'] . ' calls ' . $t['function'] . "()" . $delim;
        }

        return $cs;
    }
}
