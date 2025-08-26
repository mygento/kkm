<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

declare(strict_types=1);

namespace Mygento\Kkm\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

class CashlessColumn extends Select
{
    /**
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getPaymentMethods());
        }

        return parent::_toHtml();
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @return array
     */
    protected function getPaymentMethods()
    {
        return [
            [
                'label' => __('SBP'),
                'value' => '04', //todo add real value!!!
            ],
            [
                'label' => __('Bank Card'),
                'value' => '01', //todo add real value!!!
            ],
            //todo add other values if provided
        ];
    }
}
