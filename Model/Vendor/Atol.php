<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Vendor;

/**
 * class Atol
 */
class Atol extends \Mygento\Kkm\Model\AbstractModel
{

    /**
     * Constants
     */
    const _URL                 = 'https://online.atol.ru/possystem/v3/';
    const _code                = 'atol';
    const _operationSell       = 'sell';
    const _operationSellRefund = 'sell_refund';

    /**
     * 
     * @param type $invoice
     * @param type $order
     */
    public function sendCheque($invoice, $order)
    {
        
    }

    /**
     * 
     * @param type $creditmemo
     * @param type $order
     */
    public function cancelCheque($creditmemo, $order)
    {
        
    }

    /**
     * 
     * @param type $invoice
     */
    public function updateCheque($invoice)
    {
        
    }

    /**
     * 
     * @return boolean || string
     */
    public function getToken()
    {
        
    }

    /**
     * 
     * @param type $type || string
     * @param type $receipt
     * @param type $order
     * @return type json
     */
    protected function _generateJsonPost($type, $receipt, $order)
    {
        
    }

}
