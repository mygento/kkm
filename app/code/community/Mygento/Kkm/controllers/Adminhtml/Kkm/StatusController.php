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
            Mage::helper('adminhtml')->__('KKM Statuses'),
            Mage::helper('adminhtml')->__('KKM Statuses')
        );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent($this->getLayout()->createBlock('kkm/adminhtml_status_edit'))
            ->_addLeft($this->getLayout()->createBlock('kkm/adminhtml_status_edit_tabs'));

        $this->getLayout()->getBlock('head')->setTitle($this->__('Kkm Status'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('kkm/status');
            $model->setData($data)->setId($this->getRequest()->getParam('id'));

            $model->setOperation(
                $model->getEntityType() == Mage_Sales_Model_Order_Invoice::HISTORY_ENTITY_NAME
                    ? Mygento_Kkm_Model_Vendor_AtolAbstract::OPERATION_SELL
                    : Mygento_Kkm_Model_Vendor_AtolAbstract::OPERATION_REFUND
            );

            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__('Status was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Unable to find Status to save'));
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
