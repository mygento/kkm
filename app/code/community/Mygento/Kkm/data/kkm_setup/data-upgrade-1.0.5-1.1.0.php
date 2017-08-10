<?php

/**
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

$statuses = Mage::getModel('kkm/status')->getCollection()
//    ->addFieldToFilter('short_status', ['null' => true])
;

Mage::getSingleton('core/resource_iterator')->walk($statuses->getSelect(), ['kkm_updateStatusModel']);

function kkm_updateStatusModel($args)
{
    $status = Mage::getModel('kkm/status');
    $status->setData($args['row']);

    $dateFakeCreatedAt = "2017-07-01 00:00:00";

    $status
        ->setShortStatus('done')
        ->setEntityType($status->getEntityType())
        ->setIncrementId($status->getIncrementId())
        ->setCreatedAt($dateFakeCreatedAt)
        ->save();
}
