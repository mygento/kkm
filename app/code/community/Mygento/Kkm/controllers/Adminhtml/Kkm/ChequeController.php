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
        $entityType = strtolower($this->getRequest()->getParam('entity'));
        $id         = $this->getRequest()->getParam('id');
        $vendorName = Mage::helper('kkm')->getConfig('general/vendor');

        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Something goes wrong. Check log file.'));
            Mage::helper('kkm')->addLog('Invalid url. No id or invalid entity type. Params: ');
            Mage::helper('kkm')->addLog($this->getRequest()->getParams());
            $this->_redirectReferer();

            return;
        }

        $entity = Mage::getModel('sales/order_' . $entityType)->load($id);

        if (!$entity->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Something goes wrong. Check log file.'));
            Mage::helper('kkm')->addLog('Entity with Id from request does not exist. Id: ' . $id);
            $this->_redirectReferer();

            return;
        }

        $vendor = Mage::getModel('kkm/vendor_' . $vendorName);

        $method = 'sendCheque';
        if ($entityType == 'creditmemo') {
            $method  = 'cancelCheque';
            $comment = 'Refund was sent to KKM. Status of the transaction see in orders comment.';
        } else {
            $comment = 'Cheque was sent to KKM. Status of the transaction see in orders comment.';
        }

        $vendor->$method($entity, $entity->getOrder());

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__($comment));

        $this->_redirectReferer();
    }

    public function getlogAction()
    {
        $logDir = Mage::getBaseDir('var') . DS . 'log';
        $file   = $logDir . DS . Mage::helper('kkm')->getLogFilename();

        $transfer = new Varien_File_Transfer_Adapter_Http();
        $transfer->send($file);

        // @codingStandardsIgnoreStart
        return;
        // @codingStandardsIgnoreEnd
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
        $action = strtolower($this->getRequest()->getActionName());

        switch ($action) {
            case 'getlog':
                $aclResource = 'kkm_cheque/getlog';
                break;
            case 'resend':
                $aclResource = 'kkm_cheque/resend';
                break;
            default:
                $aclResource = 'kkm_cheque';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
}
