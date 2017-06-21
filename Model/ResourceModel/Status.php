<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\ResourceModel;

class Status extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('mygento_kkm_status', 'id');
    }

    /**
     * Load an object
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array $data
     * @return $this
     */
    public function loadTransaction(\Magento\Framework\Model\AbstractModel $object, $data)
    {
        $connection = $this->getConnection();
        if ($connection && !empty($data)) {
            $select = $this->_getLoadMultipleSelect($data, $object);
            $data   = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Retrieve select object for load multiple object data
     *
     * @param string $field
     * @param mixed $value
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getLoadMultipleSelect($data, $object)
    {
        $where = [];
        foreach ($data as $field => $value) {
            $field   = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
            $where[] = $field . '=' . $value;
        }
        $select = $this->getConnection()->select()->from($this->getMainTable())->where(implode(' AND ', $where));

        return $select;
    }

}
