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
        $helper     = Mage::helper('kkm');
        $vendor     = $helper->getVendorModel();

        if (!$vendor) {
            Mage::getSingleton('adminhtml/session')
                ->addError($helper->__('KKM Vendor not found.') . ' ' . $helper->__('Check KKM module settings.'));
            $this->_redirectReferer();

            return;
        }

        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('Something goes wrong. Check logs.'));
            $helper->addLog('Invalid url. No id or invalid entity type. Params: ', Zend_Log::ERR);
            $helper->addLog($this->getRequest()->getParams(), Zend_Log::ERR);
            $this->_redirectReferer();

            return;
        }

        $entity = Mage::getModel('sales/order_' . $entityType)->load($id);

        if (!$entity->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('Something goes wrong. Check log file.'));
            $helper->addLog('Entity with Id from request does not exist. Id: ' . $id, Zend_Log::ERR);
            $this->_redirectReferer();

            return;
        }

        $method = 'sendCheque';
        if ($entityType == 'creditmemo') {
            $method  = 'cancelCheque';
            $comment = 'Refund was sent to KKM. Status of the transaction see in orders comment.';
        } else {
            $comment = 'Cheque was sent to KKM. Status of the transaction see in orders comment.';
        }

        try {
            $vendor->$method($entity, $entity->getOrder());
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
            $this->_redirectReferer();
            return;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__($comment));

        $this->_redirectReferer();
    }

    public function checkstatusAction()
    {
        $uuid       = strtolower($this->getRequest()->getParam('uuid'));
        $helper     = Mage::helper('kkm');
        $vendor     = $helper->getVendorModel();

        if (!$uuid) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('Uuid can not be empty.'));
            $this->_redirectReferer();

            return;
        }

        if (!$vendor) {
            Mage::getSingleton('adminhtml/session')
                ->addError($helper->__('KKM Vendor not found.') . ' ' . $helper->__('Check KKM module settings.'));
            $this->_redirectReferer();

            return;
        }

        $result = $vendor->checkStatus($uuid);

        if (!$result) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('Can not check status of the transaction.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('Status was updated.'));
        }

        $this->_redirectReferer();
    }

    public function viewlogsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function clearlogsAction()
    {
        $model      = Mage::getModel('kkm/log_entry');
        $resource   = $model->getResource();
        $connection = $resource->getReadConnection();

        /* @see Varien_Db_Adapter_Pdo_Mysql - For Magento > 1.5 */
        if (!method_exists($connection, 'truncateTable')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('kkm')->__('Your Magento is too old. Please clear logs manually.'));
            $this->_redirectReferer();

            return;
        }
        $connection->truncateTable($resource->getMainTable());

        if (method_exists($connection, 'changeTableAutoIncrement')) {
            /* @see Varien_Db_Adapter_Pdo_Mysql - For Magento > 1.7 */
            $connection->changeTableAutoIncrement($resource->getMainTable(), 1);
        }

        $this->_redirectReferer();
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());

        switch ($action) {
            case 'viewlogs':
                $aclResource = 'kkm_cheque/viewlogs';
                break;
            case 'resend':
                $aclResource = 'kkm_cheque/resend';
                break;
            case 'checkstatus':
                $aclResource = 'kkm_cheque/checkstatus';
                break;
            case 'clearlogs':
                $aclResource = 'kkm_cheque/clearlogs';
                break;
            default:
                $aclResource = 'kkm_cheque';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
}
