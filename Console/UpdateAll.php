<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAll extends AbstractUpdateStatus
{
    public const COMMAND = 'mygento:kkm:update-all';
    public const COMMAND_DESCRIPTION = 'Get status of all await transactions from Kkm and save it.';

    public const ARGUMENT_STORE_ID = 'store_id';
    public const ARGUMENT_STORE_ID_DESCRIPTION = 'Store ID whose transactions will be updated';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_GLOBAL);

        $paramStoreId = (int) $input->getArgument(self::ARGUMENT_STORE_ID);

        if ($paramStoreId <= 0) {
            $output->writeln('<error>Store ID must be positive number</error>');

            return Cli::RETURN_FAILURE;
        }

        if (!$this->kkmHelper->isVendorNeedUpdateStatus($paramStoreId)) {
            $output->writeln($this->getNoNeedUpdateMessage($paramStoreId));

            return Cli::RETURN_SUCCESS;
        }

        $uuids = $this->transactionHelper->getAllWaitUuids($paramStoreId);
        $this->updateUuids($uuids, $output);

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
            self::ARGUMENT_STORE_ID,
            InputArgument::REQUIRED,
            self::ARGUMENT_STORE_ID_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command updates status of all transactions with status 'wait' of specific store id.
To update all transaction with status 'wait' and store id 1:
      <comment>%command.full_name% 1</comment>
HELP
        );
        parent::configure();
    }
}
