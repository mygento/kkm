<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Adminhtml_Kkm_StatusController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('kkm')->__('KKM Statuses'));
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('kkm/status')->load($id);
        if (!$model->getId() && $id != 0) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('kkm')->__('Status does not exist')
            );
            $this->_redirect('*/*/');
        }

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        Mage::register('kkm_status_data', $model);
        $this->loadLayout();
        $this->_setActiveMenu('kkm/statuses');
        $this->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Pvz Manager'),
            Mage::helper('adminhtml')->__('Pvz Manager')
        );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        //TODO
        $this->_addContent($this->getLayout()->createBlock('kkm/adminhtml_status_edit'))
            ->_addLeft($this->getLayout()->createBlock('kkm/adminhtml_status_edit_tabs'));
        $this->renderLayout();
    }

    //TODO
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('cdek/pvz');
            $model->setData($data)->setId($this->getRequest()->getParam('id'));
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cdek')->__('Pvz was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cdek')->__('Unable to find Pvz to save'));
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());

        switch ($action) {
            case 'index':
                $aclResource = 'kkm_cheque/viewstatuses';
                break;
            case 'new':
                $aclResource = 'kkm_cheque/newstatus';
                break;
            default:
                $aclResource = 'kkm_cheque';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
}
