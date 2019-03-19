<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mygento\Kkm\Helper\Error as KkmHelper;

/**
 * Class UpdateOrderStatuses
 * @package Mygento\Kkm\Setup\Patch\Data
 */
class UpdateOrderStatuses implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateOrderStatuses constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        // Insert statuses
        $this->moduleDataSetup->getConnection()->insertOnDuplicate(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status' => KkmHelper::ORDER_KKM_FAILED_STATUS, 'label' => 'KKM Failed']
        );

        //Bind status to state
        $states = [
            [
                'status' => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state' => 'processing',
                'is_default' => 0,
            ],
            [
                'status' => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state' => 'complete',
                'is_default' => 0,
            ],
            [
                'status' => KkmHelper::ORDER_KKM_FAILED_STATUS,
                'state' => 'closed',
                'is_default' => 0,
            ],
        ];
        foreach ($states as $state) {
            $this->moduleDataSetup->getConnection()->insertOnDuplicate(
                $this->moduleDataSetup->getTable('sales_order_status_state'),
                $state
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
