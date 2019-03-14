<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
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
 * Class SendRefund
 * @package Mygento\Kkm\Console
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendRefund extends Command
{
    const ARGUMENT_ENTITY_ID = 'id';
    const ARGUMENT_ENTITY_ID_DESCRIPTION = 'Creditmemo IncrementId';
    const COMMAND_SEND_REFUND = 'mygento:atol:refund';
    const COMMAND_DESCRIPTION = 'Sends refund to Atol.';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * @var \Magento\Sales\Model\Order\CreditmemoRepository
     */
    private $creditmemoRepo;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo
     */
    private $creditmemoResource;
    /**
     * @var \Mygento\Kkm\Model\Processor
     */
    private $processor;
    /**
     * @var \Mygento\Kkm\Helper\Transaction
     */
    private $transactionHelper;

    /**
     * SendRefund constructor.
     * @param \Mygento\Kkm\Model\Processor $processor
     * @param Transaction $transactionHelper
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Sales\Model\Order\CreditmemoRepository $creditmemoRepo
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo $creditmemoResource
     */
    public function __construct(
        \Mygento\Kkm\Model\Processor $processor,
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Framework\App\State $state,
        \Magento\Sales\Model\Order\CreditmemoRepository $creditmemoRepo,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo $creditmemoResource
    ) {
        parent::__construct();

        $this->appState       = $state;
        $this->creditmemoRepo = $creditmemoRepo;
        $this->creditmemoResource = $creditmemoResource;
        $this->processor = $processor;
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
        $creditmemo = $this->creditmemoRepo->create();
        $this->creditmemoResource->load($creditmemo, $incrementId, 'increment_id');

        if (!$creditmemo->getId()) {
            throw NoSuchEntityException::singleField('increment_id', $incrementId);
        }

        //Oтправка
        $output->writeln("<comment>1. Sending creditmemo {$incrementId} ...</comment>");

        $this->processor->proceedRefund($creditmemo, true);

        $transactions = $this->transactionHelper->getTransactionsByCreditmemo($creditmemo);

        foreach ($transactions as $transaction) {
            $status     = $transaction->getKkmStatus();
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
        $this->setName(self::COMMAND_SEND_REFUND);
        $this->setDescription(self::COMMAND_DESCRIPTION);
        $this->addArgument(
            self::ARGUMENT_ENTITY_ID,
            InputArgument::REQUIRED,
            self::ARGUMENT_ENTITY_ID_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command sends existing creditmemo to ATOL.
To send:
      <comment>%command.full_name% 100050324</comment>
HELP
        );
        parent::configure();
    }
}
