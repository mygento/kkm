<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     *
     * @param type $text
     */
    public function addLog($text)
    {
        if (Mage::getStoreConfig('kkm/general/debug')) {
            Mage::log($text, null, 'kkm.log', true);
        }
    }

    /**
     *
     * @param type string
     * @return mixed
     */
    public function getConfig($param)
    {
        return Mage::getStoreConfig('mygento/kkm/' . $param);
    }

    public function requestApiPost($url, $arpost)
    {
        // @codingStandardsIgnoreStart
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($arpost) ? http_build_query($arpost) : $arpost);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->addLog('Curl error: ' . curl_error($ch));
            return false;
        }
        curl_close($ch);
        // @codingStandardsIgnoreEnd
        $this->addLog($result);
        return $result;
    }
}
