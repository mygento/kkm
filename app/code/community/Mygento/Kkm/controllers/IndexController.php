<?php

class Mygento_Kkm_IndexController extends Mage_Core_Controller_Front_Action
{
    public function callbackAction()
    {
        $param_uuid = $this->getRequest()->getParam('uuid');

        if (!$param_uuid) {
            return;
        }

        $json = file_get_contents("php://input");

        $statusModel = Mage::getModel('kkm/status')->load($param_uuid, 'uuid');
        $statusModel->setStatus($json)->save();
    }

}
