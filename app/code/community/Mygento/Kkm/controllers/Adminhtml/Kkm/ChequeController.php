<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Adminhtml_Kkm_ChequeController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('kkm/cheque')->_addBreadcrumb(Mage::helper('kkm')->__('Cheque Manager'), Mage::helper('kkm')->__('Cheque Manager'));
        return $this;
    }

    public function resendAction()
    {
        $entityType = $this->getRequest()->getParam('entity');
        $id         = $this->getRequest()->getParam('id');

        if (!$entityType || !$id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Something goes wrong. Check log file.'));
            Mage::helper('kkm')->addLog('Invalid url. No id or entity type.');
            $this->_redirectReferer();

            return;
        }

        $entity = Mage::getModel('sales/order_' . $entityType)->load($id);

        $vendor = Mage::getModel('kkm/vendor_' . Mage::helper('kkm')->getConfig('general/vendor'));
        $vendor->sendCheque($entity, $entity->getOrder());

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__('Cheque was sent to KKM. Status of the transaction see in orders comment.'));

        $this->_redirectReferer();
    }

    public function getlogAction()
    {
        $logDir = Mage::getBaseDir('var') . DS . 'log';
        $file   = $logDir . DS . Mage::helper('kkm')->getLogFilename();

        $transfer = new Varien_File_Transfer_Adapter_Http();
        $transfer->send($file);
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
