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
    const _operationGetToken   = 'getToken';
    const _operationGetReport  = 'report';

    protected $token;

    /**
     * 
     * @param type $invoice
     * @param type $order
     */
    public function sendCheque($invoice, $order)
    {
        $type   = 'invoice_';
        $helper = $this->_kkmHelper;
        try {
            $token = $this->getToken();
        } catch (\Exception $e) {
            $helper->addLog($e->getMessage(), \Zend\Log\Logger::ERR);

            return false;
        }
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
     * @throws Exception
     */
    public function getToken($renew = false)
    {
        if (!$renew && $this->token) {
            return $this->token;
        }
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
