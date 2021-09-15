<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\Console\Cli;
use Magento\Store\Api\StoreRepositoryInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Helper\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatus extends Command
{
    private const TRANSACTION_UUID_ARGUMENT = 'transaction_uuid';
    private const TRANSACTION_UUID_ARGUMENT_DESCRIPTION = 'UUID (Transaction id) or "all" to update all';
    private const COMMAND = 'mygento:kkm:update';
    private const COMMAND_DESCRIPTION = 'Get status from Atol and save it.';

    private const RUN_ALL_PARAM = 'all';

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\UpdateInterface
     */
    protected $updateProcessor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $kkmHelper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * UpdateStatus constructor.
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param TransactionHelper $transactionHelper
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     */
    public function __construct(
        UpdateInterface $updateProcessor,
        TransactionHelper $transactionHelper,
        StoreRepositoryInterface $storeRepository,
        Data $kkmHelper,
        Request $requestHelper
    ) {
        parent::__construct();

        $this->updateProcessor = $updateProcessor;
        $this->transactionHelper = $transactionHelper;
        $this->storeRepository = $storeRepository;
        $this->kkmHelper = $kkmHelper;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transactionUuid = $input->getArgument(self::TRANSACTION_UUID_ARGUMENT);

        if ($transactionUuid !== self::RUN_ALL_PARAM) {
            $entityStoreId = $this->getEntityStoreId($transactionUuid);

            if (!$this->kkmHelper->isVendorNeedUpdateStatus($entityStoreId)) {
                $output->writeln($this->getNoNeedUpdateMessage($entityStoreId));

                return Cli::RETURN_SUCCESS;
            }

            $output->writeln("<comment>Updating {$transactionUuid} ...</comment>");

            return $this->updateOne($output, $transactionUuid);
        }

        $i = 1;
        foreach ($this->storeRepository->getList() as $store) {
            if (!$this->kkmHelper->isVendorNeedUpdateStatus($store->getId())) {
                $output->writeln($this->getNoNeedUpdateMessage($store->getId()));
            }

            $uuids = $this->transactionHelper->getWaitUuidsByStore($store->getId());
            foreach ($uuids as $uuid) {
                $output->writeln("<comment>${i} Updating {$uuid} ...</comment>");
                $this->updateOne($output, $uuid);
                $i++;
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND);
        $this->setDescription(self::COMMAND_DESCRIPTION);
        $this->addArgument(
            self::TRANSACTION_UUID_ARGUMENT,
            InputArgument::REQUIRED,
            self::TRANSACTION_UUID_ARGUMENT_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command updates status of transaction.
To update one:
      <comment>%command.full_name% 290f5207-e555-402d-88b6-fcccab9a4024</comment>
To update all transaction with status 'wait':
      <comment>%command.full_name% </comment>
HELP
            . self::RUN_ALL_PARAM
        );
        parent::configure();
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function getNoNeedUpdateMessage($storeId)
    {
        return sprintf(
            "<info>Vendor '%s' which configured to store with id '%s' does not need update status.</info>",
            $this->kkmHelper->getCurrentVendorCode($storeId),
            $storeId
        );
    }

    /**
     * @param string $uuid
     * @throws \Exception
     * @return int|null
     */
    private function getEntityStoreId($uuid)
    {
        return $this->requestHelper->getEntityByUuid($uuid)->getStoreId();
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $uuid
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return int
     */
    private function updateOne($output, $uuid)
    {
        //Обновление статуса
        $response = $this->updateProcessor->proceedSync($uuid);

        if ($response->isFailed() || $response->getError()) {
            $output->writeln("<error>Status: {$response->getStatus()}</error>");
            $output->writeln("<error>Uuid: {$response->getIdForTransaction()}</error>");
            $output->writeln("<error>Text: {$response->getErrorMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Status: {$response->getStatus()}</info>");
        $output->writeln("<info>Uuid: {$response->getIdForTransaction()}</info>");

        return Cli::RETURN_SUCCESS;
    }
}
