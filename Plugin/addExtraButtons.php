<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Plugin;

class addExtraButtons
{
    public function beforePushButtons(
    \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    )
    {

        $this->_request = $context->getRequest();
        if ($this->_request->getFullActionName() == 'sales_order_invoice_view') {
            $buttonList->add(
                'mybutton',
                ['label' => __('My Button'), 'onclick' => 'setLocation(window.location.href)', 'class' => 'reset'],
                -1
            );
        }
    }

}
