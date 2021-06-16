<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Helper\Transaction;
use Mygento\Kkm\Model\Atol\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendSell
 * @package Mygento\Kkm\Console
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendResell extends Command
{
    public const ARGUMENT_ENTITY_ID = 'id';
    public const ARGUMENT_ENTITY_ID_DESCRIPTION = 'Invoice IncrementId';
    public const COMMAND_SEND_SELL = 'mygento:atol:resell';
    public const COMMAND_DESCRIPTION = 'Sends resell to Atol. Resell means refund and then sell.';
    public const FORCE_INCREASE_EXT_ID = 'force';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
     */
    private $processor;

    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * SendSell constructor.
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param Transaction $transactionHelper
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
     */
    public function __construct(
        \Mygento\Kkm\Api\Processor\SendInterface $processor,
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
        $forceIncreaseExtId = $input->getOption(self::FORCE_INCREASE_EXT_ID);

        $invoice = $this->invoiceFactory->create()->loadByIncrementId($incrementId);
        if (!$invoice->getId()) {
            throw NoSuchEntityException::singleField('increment_id', $incrementId);
        }

        //Oтправка
        $output->writeln("<comment>1. Sending refund of the invoice {$incrementId} ...</comment>");

        try {
            $this->processor->proceedResellRefund($invoice, true, true, $forceIncreaseExtId);
        } catch (CreateDocumentFailedException $e) {
            $error = $e->getMessage();
            $error .= $e->getResponse() ? ' ' . $e->getResponse()->getErrorMessage() : '';

            $output->writeln("<error>Error: {$error}</error>");
        }

        $transactions = $this->transactionHelper->getTransactionsByInvoice($invoice, true);

        foreach ($transactions as $transaction) {
            $status = $transaction->getKkmStatus();
            $additional = $transaction->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            $transactionTypeText = ucfirst(trim($transaction->getTxnType(), 'fiscal_'));

            $message = $additional[Transaction::ERROR_MESSAGE_KEY] ?? $additional[Transaction::RAW_RESPONSE_KEY];

            $output->writeln('');
            if ($status != Response::STATUS_DONE && $status != Response::STATUS_WAIT) {
                $output->writeln("<error>{$transactionTypeText}</error>");
                $output->writeln("<error>Status: {$status}</error>");
                $output->writeln("<error>Uuid: {$transaction->getTxnId()}</error>");
                $output->writeln("<error>Text: {$message}</error>");

                continue;
            }
            $output->writeln("<comment>{$transactionTypeText}</comment>");
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
        )
            ->addOption(
                self::FORCE_INCREASE_EXT_ID,
                'f',
                InputOption::VALUE_NONE,
                'Force increase external_id'
            );

        $this->setHelp(
            <<<HELP
This command makes resell operation. 
1. The command sends 'refund' of the previous invoice 
2. new 'sell' operation will be sent automatically if 'refund' is done.

To send:
      <comment>%command.full_name% 100050324</comment>
HELP
        );
        parent::configure();
    }
}
