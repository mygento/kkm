<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Status_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('status_form', array('legend' => Mage::helper('kkm')->__('Status Information')));

        if (Mage::registry('kkm_status_data') && array_key_exists('id', Mage::registry('kkm_status_data')->getData())) {
            $fieldset->addField('id', 'hidden', array('label' => Mage::helper('kkm')->__('ID'), 'name' => 'id', 'required' => true));
        }

        $fieldset->addField('entity_type', 'select', array(
            'label' => Mage::helper('kkm')->__('Entity type'),
            'name' => 'entity_type',
            'values' => [
                ['value' => Mage_Sales_Model_Order_Invoice::HISTORY_ENTITY_NAME, 'label' => 'Invoice'],
                ['value' => Mage_Sales_Model_Order_Creditmemo::HISTORY_ENTITY_NAME, 'label' => 'Creditmemo'],
            ],
            'after_element_html' => 'Тип сущности, чек по которой был отправлен',
        ));

        $fieldset->addField('uuid', 'text', array(
            'label' => Mage::helper('kkm')->__('Uuid'),
            'name' => 'uuid',
        ));

        $fieldset->addField('external_id', 'text', array(
            'label' => Mage::helper('kkm')->__('External Id'),
            'name' => 'external_id',
            'required' => true,
        ));

        $fieldset->addField('vendor', 'hidden', array(
            'label' => Mage::helper('kkm')->__('Vendor'),
            'name'  => 'vendor',
            'value' => 'atol',
            )
        );

        $fieldset->addField('status', 'textarea', array(
            'label' => Mage::helper('kkm')->__('Status json'),
            'name' => 'status',
            'required' => true,
            'after_element_html' => 'Если чек не дошел до сервера ККМ, то статус: \'{"initial":1}\'',
        ));

        $fieldset->addField('increment_id', 'text', array(
            'label' => Mage::helper('kkm')->__('Increment Id'),
            'name' => 'increment_id',
            'required' => true,
            'after_element_html' => 'Increment Id сущности, чек по которой был отправлен',
        ));

        $fieldset->addField('resend_count', 'text', array(
            'label' => Mage::helper('kkm')->__('Resend count'),
            'name' => 'resend_count',
            'after_element_html' => 'Количество повторных отправок',
        ));

        $fieldset->addField('short_status', 'select', array(
                'label' => Mage::helper('kkm')->__('Short status'),
                'name' => 'short_status',
                'values' => [
                    ['value' => null, 'label' => 'Статус неизвестен'],
                    ['value' => 'done', 'label' => 'Done'],
                    ['value' => 'wait', 'label' => 'Wait'],
                    ['value' => 'fail', 'label' => 'Fail'],
                ],
                'after_element_html' => 'Если данные не дошли до ККМ - то оставить \'Статус неизвестен\'',
            )
        );

        if (Mage::getSingleton('adminhtml/session')->getData('kkm_status_data')) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getData('kkm_status_data'));
            Mage::getSingleton('adminhtml/session')->setData('kkm_status_data', null);
        } elseif (Mage::registry('kkm_status_data')) {
            $form->setValues(Mage::registry('kkm_status_data')->getData());
        }
        return parent::_prepareForm();
    }
}
