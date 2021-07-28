<?php

namespace Mygento\Kkm\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Mygento\Kkm\Api\ResenderInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Error;

class Resender implements ResenderInterface
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var SendInterface
     */
    private $sendProcessor;

    /**
     * @var Data
     */
    private $configHelper;

    /**
     * @var Error
     */
    private $errorHelper;

    /**
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param SendInterface $sendProcessor
     * @param Data $configHelper
     * @param Error $errorHelper
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        SendInterface $sendProcessor,
        Data $configHelper,
        Error $errorHelper
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->sendProcessor = $sendProcessor;
        $this->configHelper = $configHelper;
        $this->errorHelper = $errorHelper;
    }

    /**
     * @param int $entityId
     * @param string $entityType
     * @param bool $needExtIdIncr
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Throwable
     */
    public function resend($entityId, $entityType, $needExtIdIncr = false)
    {
        try {
            switch ($entityType) {
                case 'invoice':
                    $entity = $this->invoiceRepository->get($entityId);
                    $this->sendProcessor->proceedSell($entity, true, true, $needExtIdIncr);
                    break;
                case 'creditmemo':
                    $entity = $this->creditmemoRepository->get($entityId);
                    $this->sendProcessor->proceedRefund($entity, true, true, $needExtIdIncr);
                    break;
            }
        } catch (NoSuchEntityException $exc) {
            $this->configHelper->error("Entity {$entityType} with Id {$entityId} not found.");

            throw $exc;
        } catch (\Exception | \Throwable $exc) {
            $this->configHelper->error('Resend failed. Reason: ' . $exc->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);

            throw $exc;
        }
    }
}