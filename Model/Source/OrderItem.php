<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class OrderItem implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resource)
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * @inherit
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $table = $this->connection->describeTable(
                $this->resource->getTableName('sales_order_item')
            );

            foreach ($table as $fieldName => $fieldData) {
                $this->options[] = [
                    'value' => $fieldName,
                    'label' => $fieldData['COLUMN_NAME'],
                ];
            }
        }

        return $this->options;
    }
}
