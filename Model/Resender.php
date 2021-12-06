<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\ResenderInterface;
use Mygento\Kkm\Exception\ResendAvailabilityException;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Error;
use Mygento\Kkm\Model\Resend\ValidatorInterface as ResendValidator;

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
     * @var ResendValidator
     */
    private $resendValidator;

    /**
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param SendInterface $sendProcessor
     * @param Data $configHelper
     * @param Error $errorHelper
     * @param ResendValidator $resendValidator
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        SendInterface $sendProcessor,
        Data $configHelper,
        Error $errorHelper,
        ResendValidator $resendValidator
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->sendProcessor = $sendProcessor;
        $this->configHelper = $configHelper;
        $this->errorHelper = $errorHelper;
        $this->resendValidator = $resendValidator;
    }

    /**
     * @param int $entityId
     * @param string $entityType
     * @param bool $needExtIdIncr
     * @throws NoSuchEntityException
     * @throws ResendAvailabilityException
     * @throws \Throwable
     */
    public function resendSync($entityId, $entityType, $needExtIdIncr = false)
    {
        $this->resend($entityId, $entityType, $needExtIdIncr);
    }

    /**
     * @param int $entityId
     * @param string $entityType
     * @param bool $needExtIdIncr
     * @throws NoSuchEntityException
     * @throws ResendAvailabilityException
     * @throws \Throwable
     */
    public function resendAsync($entityId, $entityType, $needExtIdIncr = false)
    {
        $this->resend($entityId, $entityType, $needExtIdIncr, false);
    }

    /**
     * @param $entityId
     * @param $entityType
     * @param false $needExtIdIncr
     * @param bool $sync
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws ResendAvailabilityException
     * @throws \Throwable
     */
    private function resend($entityId, $entityType, $needExtIdIncr = false, $sync = true)
    {
        try {
            switch ($entityType) {
                case 'invoice':
                    $entity = $this->invoiceRepository->get($entityId);
                    $this->resendValidator->validate($entity);
                    $this->sendProcessor->proceedSell($entity, $sync, true, $needExtIdIncr);
                    break;
                case 'creditmemo':
                    $entity = $this->creditmemoRepository->get($entityId);
                    $this->resendValidator->validate($entity);
                    $this->sendProcessor->proceedRefund($entity, $sync, true, $needExtIdIncr);
                    break;
            }
        } catch (NoSuchEntityException $exc) {
            $this->configHelper->error("Entity {$entityType} with Id {$entityId} not found.");

            throw $exc;
        } catch (ResendAvailabilityException $exc) {
            throw $exc;
        } catch (\Throwable $exc) {
            $this->configHelper->error('Resend failed. Reason: ' . $exc->getMessage());
            $this->errorHelper->processKkmChequeRegistrationError($entity, $exc);

            throw $exc;
        }
    }
}
