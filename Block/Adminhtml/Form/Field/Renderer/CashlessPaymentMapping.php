<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

declare(strict_types=1);

namespace Mygento\Kkm\Block\Adminhtml\Form\Field\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;
use Mygento\Kkm\Block\Adminhtml\Form\Field\CashlessColumn;
use Mygento\Kkm\Block\Adminhtml\Form\Field\PaymentMethodColumn;

class CashlessPaymentMapping extends AbstractFieldArray
{
    /**
     * @var BlockInterface
     */
    private $paymentMethodRenderer;

    /**
     * @var BlockInterface
     */
    private $atolCodeRenderer;

    public function __construct(
        Context $context,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentMethodRenderer(),
        ]);

        $this->addColumn('atol_cashless_payment_code', [
            'label' => __('Atol Code'),
            'renderer' => $this->getAtolCodeRenderer(),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Mapping');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];

        $paymentMethod = $row->getData('payment_method');
        if ($paymentMethod) {
            $options['option_' . $this->getPaymentMethodRenderer()->calcOptionHash($paymentMethod)] = 'selected="selected"';
        }

        $cashlessCode = $row->getData('atol_cashless_payment_code');
        if ($cashlessCode) {
            $options['option_' . $this->getAtolCodeRenderer()->calcOptionHash($cashlessCode)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @throws LocalizedException
     * @return BlockInterface
     */
    protected function getPaymentMethodRenderer()
    {
        if (!$this->paymentMethodRenderer) {
            $this->paymentMethodRenderer = $this->getLayout()->createBlock(
                PaymentMethodColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]],
            );
            $this->paymentMethodRenderer->setClass('payment_method_select');
        }

        return $this->paymentMethodRenderer;
    }

    /**
     * @throws LocalizedException
     * @return BlockInterface
     */
    protected function getAtolCodeRenderer()
    {
        if (!$this->atolCodeRenderer) {
            $this->atolCodeRenderer = $this->getLayout()->createBlock(
                CashlessColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]],
            );
            $this->atolCodeRenderer->setClass('atol_cashless_payment_code_code_select');
        }

        return $this->atolCodeRenderer;
    }
}
