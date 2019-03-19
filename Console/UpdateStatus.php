<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatus extends Command
{
    const ARGUMENT = 'param';
    const ARGUMENT_DESCRIPTION = 'UUID (Transaction id) or "all" to update all';
    const COMMAND = 'mygento:atol:update';
    const COMMAND_DESCRIPTION = 'Get status from Atol and save it.';

    const RUN_ALL_PARAM = 'all';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Transaction\Proxy
     */
    private $transactionHelper;

    /**
     * UpdateStatus constructor.
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();

        $this->appState = $state;
        $this->vendor = $vendor;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $param = $input->getArgument('param');

        $uuids = ($param === self::RUN_ALL_PARAM)
            ? $this->transactionHelper->getAllWaitUuids()
            : [$param];

        $i = 1;
        foreach ($uuids as $uuid) {
            $output->writeln("<comment>${i} Updating {$uuid} ...</comment>");
            $this->updateOne($output, $uuid);
            $i++;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return int
     */
    private function updateOne($output, $uuid)
    {
        //Обновление статуса
        $response = $this->vendor->updateStatus($uuid);

        if ($response->isFailed() || $response->getError()) {
            $output->writeln("<error>Status: {$response->getStatus()}</error>");
            $output->writeln("<error>Uuid: {$response->getUuid()}</error>");
            $output->writeln("<error>Text: {$response->getErrorMessage()}</error>");

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Status: {$response->getStatus()}</info>");
        $output->writeln("<info>Uuid: {$response->getUuid()}</info>");

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
