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

    public function getjsonAction()
    {
        $incId = $this->getRequest()->getParam('id');

        $order = Mage::getModel('sales/order')->loadByIncrementId($incId);

        if (!$order) {
            return;
        }

        $atolModel  = Mage::getModel('kkm/vendor_atol');
        $jsonToSend = $atolModel->generateJsonPost($order, 'manual');
        $filename   = "json_{$incId}.json";

        if ($jsonToSend) {
            $response = $this->getResponse();
            $response->setHeader('Content-type', 'application/json', true);
            $response->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
            return $this->getResponse()->setBody($jsonToSend);
        }

        Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('kkm')->__('Can not generate json. Last error: ') . json_last_error());
        $this->loadLayout();
        $this->renderLayout();
    }

    public function getunittestAction()
    {
        $entityType = strtolower($this->getRequest()->getParam('entity'));
        $id         = $this->getRequest()->getParam('id');

        try {
            $this->checkResendRequest();

            $entity = Mage::getModel('sales/order_' . $entityType)->load($id);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage() . ' Json error: ' . json_last_error());
            $this->_redirectReferer();

            return;
        }

        $orderSubTotal   = $entity->getData('subtotal_incl_tax');
        $orderGrandTotal = $entity->getData('grand_total');
        $orderShipping   = $entity->getData('shipping_incl_tax');
        $globalDiscount  =
            $entity->getData('reward_currency_amount')
            + $entity->getData('gift_cards_amount')
            + $entity->getData('customer_balance_amount');

        $testCode =
            "\$order = \$this->getNewOrderInstance({$orderSubTotal}, {$orderGrandTotal}, {$orderShipping}, {$globalDiscount});";

        $items = $entity->getAllVisibleItems()
            ? $entity->getAllVisibleItems()
            : $entity->getAllItems();

        foreach ($items as $item) {
            $rowTotal = $item->getData('row_total_incl_tax');
            $price    = $item->getData('price_incl_tax');
            $discount = $item->getData('discount_amount');
            $qty      = $item->getData('qty');

            $testCode .= "\n";
            $testCode .= "\$this->addItem(\$order, \$this->getItem({$rowTotal}, {$price}, {$discount}, {$qty}));";
        }
        $testCode .= "\n";

        $atolModel = Mage::getModel('kkm/vendor_atol');
        $json      = $atolModel->generateJsonPost($entity, '');
        $receipt   = json_decode($json, true);

        if (!is_array($receipt) || !isset($receipt['receipt']['items'])) {
            Mage::getSingleton('adminhtml/session')->addError('Calculation error');
            $this->_redirectReferer();

            return;
        }

        //Calculate sum
        $itemsSum = 0;
        foreach ($receipt['receipt']['items'] as $itemArray) {
            $itemsSum += $itemArray['sum'];
        }
        $shipping = end($receipt['receipt']['items']);
        $itemsSum = $itemsSum - $shipping['sum'];

        $actualArray = [
            'sum'            => round($itemsSum, 2),
            'origGrandTotal' => round($orderGrandTotal, 2)
        ];

        $actualArray['items'] = $receipt['receipt']['items'];
        $actualArrayStr       = var_export($actualArray, true);

        $testCode .= "\$actualArray = {$actualArrayStr};";
        $testCode .= "\n";
        $testCode .= "\$final['{$entityType} {$entity->getIncrementId()}'] = [\$order, \$actualArray];";


        $testCode = "//Copy and paste following content to dataProvider method of the proper class in ./tests folder.
//Choose test class in accordance with KKM module algorithm settings\n\n" . $testCode;

        $filename = "phpUnitTest_{$entityType}_{$entity->getIncrementId()}.test";

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/text', true);
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
        return $this->getResponse()->setBody($testCode);
    }

    public function viewlogsAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('kkm')->__('KKM Logs Viewer'));
        $this->renderLayout();
    }

    public function clearlogsAction()
    {
        $moduleCode = $this->getRequest()->getParam('code', 'kkm');

        $model      = Mage::getModel('kkm/log_entry');
        $resource   = $model->getResource();
        $connection = $resource->getReadConnection();

        $connection->delete(
            $resource->getMainTable(),
            "`module_code` = '{$moduleCode}'"
        );

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
            case 'getjson':
                $aclResource = 'kkm_cheque/getjson';
                break;
            case 'getunittest':
                $aclResource = 'kkm_cheque/getunittest';
                break;
            default:
                $aclResource = 'kkm_cheque';
                break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }
}
