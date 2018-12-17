<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Status_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {
        parent::_construct();
        $this->setId('kkmStatusGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('kkm/status')
            ->getCollection()
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $shortStatuses = [
            'done' => 'Done',
            'wait' => 'Wait',
            'fail' => 'Fail',
        ];

        $this->addColumn('id', [
            'header' => Mage::helper('kkm')->__('ID'),
            'align'  => 'right',
            'width'  => '30px',
            'index'  => 'id',
        ]);
        $this->addColumn('uuid', [
            'header' => Mage::helper('kkm')->__('UUID'),
            'align'  => 'left',
            'width'  => '60px',
            'index'  => 'uuid',
        ]);
        $this->addColumn('external_id', [
            'header' => Mage::helper('kkm')->__('External Id'),
            'align'  => 'left',
            'width'  => '30px',
            'index'  => 'external_id',
        ]);
        $this->addColumn('short_status', [
            'header' => Mage::helper('kkm')->__('Status'),
            'align'  => 'center',
            'width'  => '20px',
            'index'  => 'short_status',
            'type'   => 'options',
            'options' => $shortStatuses,
        ]);
        $this->addColumn('status', [
            'header' => Mage::helper('kkm')->__('Full status'),
            'align'  => 'left',
            'index'  => 'status',
        ]);

        $this->addColumn('updated_at', [
            'header' => Mage::helper('kkm')->__('Updated at'),
            'align'  => 'right',
            'type'   => 'datetime',
            'width'  => '100px',
            'index'  => 'updated_at',
        ]);

        return parent::_prepareColumns();
    }
}
