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
    protected $forceFlag = false;

    protected function _initAction()
    {
        return $this;
    }

    public function resendAction()
    {
        $entityType = strtolower($this->getRequest()->getParam('entity'));
        $id         = $this->getRequest()->getParam('id');
        $helper     = Mage::helper('kkm');
        $vendor     = $helper->getVendorModel();

        try {
            $this->checkResendRequest();

            $entity      = Mage::getModel('sales/order_' . $entityType)->load($id);
            $method      = $this->getMethodForResend($entityType);
            $statusModel = Mage::getModel('kkm/status')->loadByEntity($entity);

            if (!$this->forceFlag) {
                //Process existing transaction. It depends on error code, status and some other conditions
                $vendor->processExistingTransactionBeforeSending($statusModel);
            }

            //Send to KKM invoice or refund
            $vendor->$method($entity, $entity->getOrder());
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
            $this->_redirectReferer();

            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirectReferer();

            return;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('kkm')->__('Data was sent to KKM. Status of the transaction see in orders comment.'));

        $this->_redirectReferer();
    }

    public function forceresendAction()
    {
        $this->forceFlag = true;

        return $this->resendAction();
    }

    public function checkstatusAction()
    {
        $uuid   = strtolower($this->getRequest()->getParam('uuid'));
        $helper = Mage::helper('kkm');
        $vendor = $helper->getVendorModel();

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

        try {
            $result = $vendor->checkStatus($uuid);
        } catch (Mygento_Kkm_SendingException $e) {
            $helper->processError($e);
            Mage::getSingleton('adminhtml/session')->addError($e->getFullTitle());
            $this->_redirectReferer();

            return;
        } catch (Exception $e) {
            $helper->addLog($e->getMessage(), Zend_Log::WARN);
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $result = false;
        }

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
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('kkm')->__('KKM Logs Viewer'));
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

    protected function checkResendRequest()
    {
        $entityType = strtolower($this->getRequest()->getParam('entity'));
        $id         = $this->getRequest()->getParam('id');
        $helper     = Mage::helper('kkm');
        $vendor     = $helper->getVendorModel();

        if (!$vendor) {
            throw new Exception($helper->__('KKM Vendor not found.') . ' ' . $helper->__('Check KKM module settings.'));
        }

        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            $helper->addLog('Invalid url for resend action. No id or invalid entity type. Params: ', Zend_Log::ERR);
            $helper->addLog($this->getRequest()->getParams(), Zend_Log::ERR);

            throw new Exception($helper->__('Something goes wrong. Invalid URL for resend. Check logs.'));
        }

        $entity = Mage::getModel('sales/order_' . $entityType)->load($id);

        if (!$entity->getId()) {
            $helper->addLog('Entity with Id from request does not exist. Id: ' . $id, Zend_Log::ERR);
            throw new Exception($helper->__('Something goes wrong. Invalid URL for resend. Check logs.'));
        }
    }

    protected function getMethodForResend($entityType)
    {
        $method = !$this->forceFlag ? 'sendCheque' : 'forceSendCheque';
        if ($entityType == 'creditmemo') {
            $method = !$this->forceFlag ? 'cancelCheque' : 'forceCancelCheque';
        }

        return $method;
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
            case 'forceresend':
                $aclResource = 'kkm_cheque/forceresend';
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
