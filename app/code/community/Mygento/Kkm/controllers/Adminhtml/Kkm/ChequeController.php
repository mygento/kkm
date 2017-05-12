<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Adminhtml_Kkm_ChequeController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('kkm/cheque')->_addBreadcrumb(Mage::helper('kkm')->__('Cheque Manager'), Mage::helper('kkm')->__('Cheque Manager'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('kkm/cheque')->load($id);
        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('cheque_data', $model);
            $this->loadLayout();
            $this->_setActiveMenu('kkm/cheque');
            $this->_addBreadcrumb(Mage::helper('kkm')->__('Cheque Manager'), Mage::helper('kkm')->__('Cheque Manager'));
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('kkm/adminhtml_cheque_edit'))->_addLeft($this->getLayout()->createBlock('kkm/adminhtml_cheque_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Cheque does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('kkm/cheque');
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = implode(',', $this->getRequest()->getParam($key));
                }
            }
            $model->setData($data)->setId($this->getRequest()->getParam('id'));
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__('Cheque was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Unable to find cheque  to save'));
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/kkm');
    }
}
