<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_IndexController extends Mage_Core_Controller_Front_Action
{
    public function callbackAction()
    {
        // @codingStandardsIgnoreStart
        $json = file_get_contents('php://input');

        if ($json) {
            $jsonDecode = json_decode($json);
            Mage::helper('kkm')->addLog('callbackAction $json: ' . $json);

            if (!$jsonDecode->uuid) {
                Mage::helper('kkm')->addLog('callbackAction $json failed. $json: ' . $json, Zend_Log::WARN);

                return;
            }

            //Sometimes callback is received when transaction is not saved yet. In order to avoid this
            sleep(3);
            // @codingStandardsIgnoreEnd

            $statusModel = Mage::getModel('kkm/status')->load($jsonDecode->uuid, 'uuid');

            if (!$statusModel->getId()) {
                Mage::helper('kkm')->addLog('UUID not found. Uuid: ' . $jsonDecode->uuid . ' Full callback: ' . $json, Zend_Log::WARN);

                return;
            }

            $statusModel
                ->setShortStatus(isset($jsonDecode->status) ? $jsonDecode->status : null)
                ->setStatus($json)
                ->save();

            //Add comment to order about callback data
            Mage::helper('kkm')->saveCallback($statusModel);

            try {
                $vendor = Mage::helper('kkm')->getVendorModel();

                if (!$vendor) {
                    Mage::helper('kkm')->addLog('Attempt to save callback. KKM Vendor not found.', Zend_Log::WARN);

                    return;
                }

                $vendor->validateResponse($json);
            } catch (Exception $e) {
                $entity = Mage::helper('kkm')->getEntityModelByStatusModel($statusModel);
                Mage::helper('kkm')->processError(new Mygento_Kkm_SendingException($entity, $e->getMessage(), []));
            }
        }
    }
}
