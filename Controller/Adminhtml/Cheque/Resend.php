<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Cheque;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;

class Resend extends \Magento\Backend\App\Action
{
    /** @var \Mygento\Kkm\Helper\Data */
    protected $kkmHelper;
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;
    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceFactory;
    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        parent::__construct($context);

        $this->kkmHelper            = $kkmHelper;
        $this->vendor               = $vendor;
        $this->invoiceFactory       = $invoiceFactory;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    public function execute()
    {
        try {
            $this->validateRequest();

            $entityType = strtolower($this->_request->getParam('entity'));
            $id         = $this->getRequest()->getParam('id');

            switch ($entityType) {
                case 'invoice':
                    $entity = $this->invoiceFactory->create()->load($id);
                    $comment = 'Cheque was sent to KKM. Status: %1';
                    break;

                case 'creditmemo':
                    $entity = $this->creditmemoRepository->get($id);
                    $comment = 'Refund was sent to KKM. Status: %1';
                    break;
            }

            //FIXME: чо за бред, выпилить метод send() и юзать отдельные спец методы
            $response = $this->vendor->send($entity);
            $this->getMessageManager()->addSuccessMessage(__($comment, $response->getStatus()));
        } catch (NoSuchEntityException $exc) {
            $this->getMessageManager()->addErrorMessage(
                __(ucfirst($entityType)) . " {$id} " . __('not found')
            );
            $this->kkmHelper->error("Entity {$entityType} with Id {$id} not found.");
        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
            $this->kkmHelper->error('Resend failed. Reason: ' . $exc->getMessage());
            $this->kkmHelper->processKkmChequeRegistrationError($entity, $exc);
        } finally {
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->getRefererUrl()
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function validateRequest()
    {
        $entityType = $this->getRequest()->getParam('entity');
        $id         = $this->getRequest()->getParam('id');

        if (!$entityType || !$id || !in_array($entityType, ['invoice', 'creditmemo'])) {
            $this->kkmHelper->error('Invalid url. No id or invalid entity type. Params: ');
            $this->kkmHelper->error($this->getRequest()->getParams());

            throw new ValidatorException(__('Invalid request. Check logs.'));
        }
    }
}
