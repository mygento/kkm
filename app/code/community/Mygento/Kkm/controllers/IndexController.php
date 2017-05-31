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
        // @codingStandardsIgnoreEnd

        if ($json) {
            $jsonDecode = json_decode($json);
            Mage::helper('kkm')->addLog('callbackAction $json: ' . $json);

            if (!$jsonDecode->uuid) {
                Mage::helper('kkm')->addLog('callbackAction $json failed');
                return;
            }

            $statusModel = Mage::getModel('kkm/status')->load($jsonDecode->uuid, 'uuid');
            $statusModel->setStatus($json)->save();

            //Add comment to order about callback data
            Mage::helper('kkm')->updateKkmInfoInOrder($json, $statusModel->getExternalId());
        }
    }
}
