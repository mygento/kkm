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
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatus extends Command
{
    public const ARGUMENT = 'param';
    public const ARGUMENT_DESCRIPTION = 'UUID (Transaction id) or "all" to update all';
    public const COMMAND = 'mygento:atol:update';
    public const COMMAND_DESCRIPTION = 'Get status from Atol and save it.';

    public const RUN_ALL_PARAM = 'all';

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\UpdateInterface
     */
    private $updateProcessor;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * UpdateStatus constructor.
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param TransactionHelper $transactionHelper
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        UpdateInterface $updateProcessor,
        TransactionHelper $transactionHelper,
        StoreRepositoryInterface $storeRepository
    ) {
        parent::__construct();

        $this->updateProcessor = $updateProcessor;
        $this->transactionHelper = $transactionHelper;
        $this->storeRepository = $storeRepository;
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
        $param = $input->getArgument('param');

        if ($param !== self::RUN_ALL_PARAM) {
            $output->writeln("<comment>Updating {$param} ...</comment>");

            return $this->updateOne($output, $param);
        }

        $i = 1;
        foreach ($this->storeRepository->getList() as $store) {
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
            self::ARGUMENT,
            InputArgument::REQUIRED,
            self::ARGUMENT_DESCRIPTION
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
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $uuid
     * @return int
     */
    private function updateOne($output, $uuid)
    {
        //Обновление статуса
        $response = $this->updateProcessor->proceedSync($uuid);

        if ($response->isFailed() || $response->getError()) {
            $output->writeln("<error>Status: {$response->getStatus()}</error>");
            $output->writeln("<error>Uuid: {$response->getUuid()}</error>");
            $output->writeln("<error>Text: {$response->getErrorMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Status: {$response->getStatus()}</info>");
        $output->writeln("<info>Uuid: {$response->getUuid()}</info>");

        return Cli::RETURN_SUCCESS;
    }
}
