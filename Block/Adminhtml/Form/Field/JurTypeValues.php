<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Mygento\Kkm\Block\Adminhtml\Form\Field\Renderer\JurType;
use Mygento\Kkm\Helper\Data;

class JurTypeValues extends AbstractFieldArray
{
    /**
     * @var JurType
     */
    private $isActiveRenderer = null;

    protected function _prepareToRender()
    {
        $this->addColumn(
            Data::CFG_ATTRIBUTE_VALUE,
            [
                'label' => __('Attribute Value'),
            ]
        );
        $this->addColumn(
            Data::CFG_JUR_TYPE,
            [
                'label' => __('Company Type'),
                'renderer' => $this->getJurTypeRenderer(),
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $row->setData('option_extra_attrs', []);
    }

    /**
     * @throws LocalizedException
     * @return JurType
     */
    protected function getJurTypeRenderer()
    {
        if (!$this->isActiveRenderer) {
            $this->isActiveRenderer = $this->getLayout()->createBlock(
                JurType::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->isActiveRenderer;
    }
}
