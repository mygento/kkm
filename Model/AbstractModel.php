<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model;

use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Abstract model for vendor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractModel
{

    /**
     * Constants
     */
    const ORDER_KKM_FAILED_STATUS = 'kkm_failed';

    abstract protected function sendCheque($invoice, $order);
    abstract protected function cancelCheque($creditmemo, $order);
    abstract protected function updateCheque($invoice);
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
    
        $this->_kkmHelper = $kkmHelper;
    }

    /**
     *
     * @param type $param
     * @return type
     */
    protected function getConfig($param)
    {
        return $this->_kkmHelper->getConfig($param);
    }

    /**
     *
     * @return type
     */
    protected function getVendor()
    {
        return $this->getConfig('vendor');
    }
}
