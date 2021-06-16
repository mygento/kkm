<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\ResourceModel;

/**
 * Class Log
 */
class Log extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mygento_kkm_log', 'entity_id');
    }
}
