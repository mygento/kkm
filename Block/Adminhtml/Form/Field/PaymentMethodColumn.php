<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

declare(strict_types=1);

namespace Mygento\Kkm\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Payment\Helper\Data;

class PaymentMethodColumn extends Select
{
    /**
     * @var Data
     */
    private $paymentHelper;

    public function __construct(
        Context $context,
        Data $paymentHelper,
        array $data = [],
    ) {
        $this->paymentHelper = $paymentHelper;
        parent::__construct($context, $data);
    }

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
        $methods = [];
        $allPaymentMethods = $this->paymentHelper->getPaymentMethodList();

        foreach ($allPaymentMethods as $code => $title) {
            $methods[] = [
                'value' => $code,
                'label' => $title . ' (' . $code . ')',
            ];
        }

        return $methods;
    }
}
