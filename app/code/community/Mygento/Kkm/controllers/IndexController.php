<?php

class Mygento_Kkm_IndexController extends Mage_Core_Controller_Front_Action
{
    public function callbackAction()
    {
        $json = file_get_contents("php://input");

        if ($json) {
            $jsonDecode = json_decode($json);
            Mage::helper('kkm')->addLog('callbackAction $json: ' . $json);

            if (!$jsonDecode->uuid) {
                Mage::helper('kkm')->addLog('callbackAction $json failed');
                return;
            }

            $statusModel = Mage::getModel('kkm/status')->load($jsonDecode->uuid, 'uuid');
            $statusModel->setStatus($json)->save();
        }
    }

}
