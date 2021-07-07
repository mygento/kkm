<?php

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mygento\Kkm\Model\ResourceModel\TransactionAttempt\CollectionFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Sales\Model\Order;

class MassResend extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mygento_Kkm::cheque_resend';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);

        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            /** @var Order $entity */
            foreach ($collection as $entity) {
                //send
            }

            $this->messageManager->addSuccessMessage(
                __('Cheques for %1 record(s) has been sent.', $collectionSize)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectPath = $this->filter->getComponentRefererUrl() ?: 'sales/*/';

        return $resultRedirect->setPath($redirectPath);
    }
}