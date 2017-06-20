<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Helper;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data
{

    /**
     * @var string
     */
    protected $_code = 'kkm';

    /*     * Save info about transaction to order
     * @param $getRequest string with json from vendor
     * @param $entity Invoice|Creditmemo
     * @param $order Order
     * @return bool
     */
    public function saveTransactionInfoToOrder(
        $getRequest,
        $entity,
        $order,
        $orderComment = '',
        $vendorName = 'atol'
    ) {
    
        $status = false;

        try {
            $getRequestObj = json_decode($getRequest);

            if ($getRequestObj->error == null) {
                $orderComment = $orderComment ?: 'Cheque has been sent to KKM vendor.';
                $comment      = '[' . strtoupper($vendorName) . '] '
                    . __($orderComment) . ' '
                    . ucwords($entity->getEntityType()) . ': '
                    . $entity->getIncrementId()
                    . '. Status: '
                    . ucwords($getRequestObj->status)
                    . '. Uuid: '
                    . $getRequestObj->uuid ?: 'no uuid';
            } else {
                $orderComment = $orderComment ?: 'Cheque has been rejected by KKM vendor.';
                $comment      = '[' . strtoupper($vendorName) . '] '
                    . __($orderComment) . ' '
                    . ucwords($entity->getEntityType()) . ': '
                    . $entity->getIncrementId()
                    . '. Status: '
                    . ucwords($getRequestObj->status)
                    . '. Error code: '
                    . $getRequestObj->error->code
                    . '. Error text: '
                    . $getRequestObj->error->text
                    . '. Uuid: '
                    . $getRequestObj->uuid ?: 'no uuid';

                if ($this->getConfig('mygento_kkm/general/fail_status')) {
                    $status = $this->getConfig('mygento_kkm/general/fail_status');
                }
            }

            if ($status) {
                $order->setState('processing', $status, $comment);
            } else {
                $order->addStatusHistoryComment($comment);
            }

            $order->save();
        } catch (\Exception $e) {
            $this->addLog(
                'Can not save KKM transaction info to order. Reason: ' . $e->getMessage(),
                \Zend\Log\Logger::CRIT
            );

            return false;
        }
    }
}
