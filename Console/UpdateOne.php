<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateOne extends AbstractUpdateStatus
{
    public const COMMAND = 'mygento:kkm:update-one';
    public const COMMAND_DESCRIPTION = 'Get status of specified transaction from Kkm and save it.';

    public const ARGUMENT_UUID = 'uuid';
    public const ARGUMENT_UUID_DESCRIPTION = 'UUID (Transaction id)';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_GLOBAL);

        $paramUuid = $input->getArgument(self::ARGUMENT_UUID);
        $entityStoreId = $this->getEntityStoreId($paramUuid);

        if (!$this->kkmHelper->isVendorNeedUpdateStatus($entityStoreId)) {
            $output->writeln($this->getNoNeedUpdateMessage($entityStoreId));

            return Cli::RETURN_SUCCESS;
        }

        $this->updateUuids([$paramUuid], $output);

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
            self::ARGUMENT_UUID,
            InputArgument::REQUIRED,
            self::ARGUMENT_UUID_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command updates status of specified transaction.
To update:
      <comment>%command.full_name% 290f5207-e555-402d-88b6-fcccab9a4024</comment>
HELP
        );
        parent::configure();
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
}
