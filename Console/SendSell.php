<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Helper\Transaction;
use Mygento\Kkm\Model\Atol\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendSell
 * @package Mygento\Kkm\Console
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var \Mygento\Kkm\Api\ProcessorInterface
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * @param \Mygento\Kkm\Api\ProcessorInterface $processor
     * @param Transaction $transactionHelper
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
     */
    public function __construct(
        \Mygento\Kkm\Api\ProcessorInterface $processor,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Framework\App\State $state,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
    ) {
        parent::__construct();

        $this->appState = $state;
        $this->processor = $processor;
        $this->invoiceFactory = $invoiceFactory;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return int|null
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

        $this->processor->proceedSell($invoice, true, true);

        $transactions = $this->transactionHelper->getTransactionsByInvoice($invoice);

        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            $additional = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);

            $message = isset($additional[Transaction::ERROR_MESSAGE_KEY])
                ? $additional[Transaction::ERROR_MESSAGE_KEY]
                : $additional[Transaction::RAW_RESPONSE_KEY];

            if ($status != Response::STATUS_DONE || $status != Response::STATUS_WAIT) {
                $output->writeln("<error>Status: {$status}</error>");
                $output->writeln("<error>Uuid: {$transaction->getTxnId()}</error>");
                $output->writeln("<error>Text: {$message}</error>");

                return \Magento\Framework\Console\Cli::RETURN_FAILURE;
            }

            $output->writeln("<info>Status: {$status}</info>");
            $output->writeln("<info>Uuid: {$transaction->getTxnId()}</info>");
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Configure the command
     */
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
