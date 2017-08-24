<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Severity
{

    /**
     * Options source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Zend_Log::CRIT,
                'label' => ('CRITICAL')
            ],
            [
                'value' => Zend_Log::ERR,
                'label' => ('ERROR')
            ],
            [
                'value' => Zend_Log::WARN,
                'label' => ('WARN')
            ],
            [
                'value' => Zend_Log::DEBUG,
                'label' => ('DEBUG')
            ],
        ];
    }

    public function getOptions()
    {
        $options = $this->toOptionArray();
        $values  = Mage::helper('kkm')->array_column($options, 'value');
        $labels  = Mage::helper('kkm')->array_column($options, 'label');

        return array_combine($values, $labels);
    }
}
