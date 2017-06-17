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
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $hostname        = gethostname() !== false ? gethostname() : '';
        $message         = '[' . $hostname . '] ' . $message;
        $advancedMessage = $severity <= Zend_Log::CRIT ? $this->get_callstack() : '';

        $this->_getDbLogger()
            ->setModuleCode($this->_code)
            ->setMessage($message)
            ->setTimestamp(date('Y-m-d H:i:s'))
            ->setSeverity($severity)
            ->setAdvancedInfo($advancedMessage)
            ->save();
    }

    public function _getDbLogger()
    {
        return Mage::getModel($this->_code . '/log_entry');
    }

    public function get_callstack($delim="\n") {
        $dt = debug_backtrace();
        $cs = '';
        foreach ($dt as $t) {
            $cs .= $t['file'] . ' line ' . $t['line'] . ' calls ' . $t['function'] . "()" . $delim;
        }

        return $cs;
    }
}