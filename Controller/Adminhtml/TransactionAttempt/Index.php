<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */


namespace Mygento\Kkm\Controller\Adminhtml\TransactionAttempt;

class Index extends \Mygento\Kkm\Controller\Adminhtml\TransactionAttempt
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /**
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $repository
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mygento\Kkm\Api\TransactionAttemptRepositoryInterface $repository,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($repository, $coreRegistry, $context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('Transaction Attempt'));

        //$dataPersistor = $this->_objectManager->get(\Magento\Framework\App\Request\DataPersistorInterface::class);
        //$dataPersistor->clear('kkm_transactionattempt');
        return $resultPage;
    }
}
