<?php
/**
 * @author Mygento Team
 * @copyright Copyright 2017 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

class Resend extends \Magento\Backend\App\Action
{

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $_helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mygento\Kkm\Helper\Data $helper
    ) {
    
        parent::__construct($context);
        $this->_helper = $helper;
    }

    /**
     * Main action
     */
    public function execute()
    {
        die("Admin - Mygento\\Kkm\\Controller\\Adminhtml\\Cheque\\Resend - execute() method");
    }
}
