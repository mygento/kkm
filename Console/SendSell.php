<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

class SendSell extends Command
{
    const ARGUMENT_ENTITY_ID = 'id';
    const ARGUMENT_ENTITY_ID_DESCRIPTION = 'Invoice IncrementId';
    const COMMAND_SEND_SELL = 'mygento:atol:sell';
    const COMMAND_DESCRIPTION = 'Sends sell to Atol.';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceFactory;
    /**
     * @var \Mygento\Kkm\Model\Processor
     */
    private $processor;

    public function __construct(
        \Mygento\Kkm\Model\Processor $processor,
        \Magento\Framework\App\State $state,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
    ) {
        parent::__construct();

        $this->appState = $state;
        $this->processor = $processor;
        $this->invoiceFactory = $invoiceFactory;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $incrementId = $input->getArgument('id');
        $invoice = $this->invoiceFactory->create()->loadByIncrementId($incrementId);
        if (!$invoice->getId()) {
            throw NoSuchEntityException::singleField('increment_id', $incrementId);
        }

        //Oтправка
        $output->writeln("<comment>1. Sending invoice {$incrementId} ...</comment>");

        $response = $this->processor->proceedSell($invoice, true);

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

    protected function configure()
    {
        $this->setName(self::COMMAND_SEND_SELL);
        $this->setDescription(self::COMMAND_DESCRIPTION);
        $this->addArgument(
            self::ARGUMENT_ENTITY_ID,
            InputArgument::REQUIRED,
            self::ARGUMENT_ENTITY_ID_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command sends invoice to ATOL.
To send:
      <comment>%command.full_name% 100050324</comment>
HELP
        );
        parent::configure();
    }
}
