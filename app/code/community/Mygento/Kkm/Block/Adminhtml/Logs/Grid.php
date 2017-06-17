<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Logs
 * @copyright Copyright © 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {
        parent::_construct();
        $this->setId('barcodeGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('kkm/log_entry')
            ->getCollection()
            ->addFieldToFilter('module_code', ['eq' => 'kkm'])
            ->setOrder('entity_id', 'DESC');
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header' => Mage::helper('kkm')->__('ID'),
            'align'  => 'right',
            'width'  => '30px',
            'index'  => 'entity_id',
        ]);
        $this->addColumn('message', [
            'header' => Mage::helper('kkm')->__('Message'),
            'align'  => 'left',
            'width'  => '150px',
            'index'  => 'message',
        ]);
        $this->addColumn('severity', [
            'header' => Mage::helper('kkm')->__('Severity'),
            'align'  => 'right',
            'width'  => '20px',
            'index'  => 'severity',
        ]);
        $this->addColumn('timestamp', [
            'header' => Mage::helper('kkm')->__('Time'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'timestamp',
        ]);
        $this->addColumn('advanced_info', [
            'header' => Mage::helper('kkm')->__('Info'),
            'align'  => 'right',
            'width'  => '100px',
            'index'  => 'advanced_info',
        ]);

        return parent::_prepareColumns();
    }
}
