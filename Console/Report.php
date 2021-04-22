<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Sales\Model\Order\Payment\Transaction as TransactionEntity;
use Mygento\Kkm\Helper\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Report
 * @package Mygento\Kkm\Console
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Report extends Command
{
    const ARGUMENT = 'period';
    const ARGUMENT_DESCRIPTION = 'Period. Possible values: '
    . self::TODAY_PERIOD . ', '
    . self::YESTERDAY_PERIOD . ', '
    . self::WEEK_PERIOD;
    const COMMAND = 'mygento:kkm:report';
    const COMMAND_DESCRIPTION = 'Show report of kkm transaction for period.';

    const WEEK_PERIOD = 'week';
    const YESTERDAY_PERIOD = 'yesterday';
    const TODAY_PERIOD = 'today';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Mygento\Kkm\Model\Report
     */
    private $report;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Report constructor.
     * @param \Mygento\Kkm\Model\Report $report
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Mygento\Kkm\Model\Report $report,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();

        $this->appState = $state;
        $this->report = $report;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $param = $input->getArgument(self::ARGUMENT);
        switch ($param) {
            case self::WEEK_PERIOD:
                $statistics = $this->report->getWeekStatistics();
                break;
            case self::YESTERDAY_PERIOD:
                $statistics = $this->report->getYesterdayStatistics();
                break;
            case self::TODAY_PERIOD:
            default:
                $statistics = $this->report->getTodayStatistics();
                break;
        }

        $this->parseStatistics($statistics);

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
            InputArgument::OPTIONAL,
            self::ARGUMENT_DESCRIPTION
        );
        $this->setHelp(
            <<<HELP
This command shows report of transactions.
      <comment>%command.full_name% yesterday</comment>
Today by default.
HELP
        );
        parent::configure();
    }

    /**
     * @param \Mygento\Kkm\Model\Statistics $statistics
     */
    private function parseStatistics(\Mygento\Kkm\Model\Statistics $statistics)
    {
        $commonStat = new Table($this->output);
        $commonStat
            ->setHeaders(['Статус', 'Количество'])
            ->addRow(['Done', $statistics->getDonesCount()])
            ->addRow(['Wait', $statistics->getWaitsCount()])
            ->addRow(['Not sent', $statistics->getNotSentCount()]);

        $detailedStat = new Table($this->output);
        $detailedStat->setHeaders(
            [
                'Created at',
                'Status',
                'UUID',
                'Operation',
                'Increment Id',
                'Message',
            ]
        );

        /**
         * @var \Mygento\Kkm\Api\Data\TransactionAttemptInterface[]
         */
        $notDone = array_merge(
            $statistics->getFails(),
            $statistics->getUnknowns(),
            $statistics->getWaits()
        );

        foreach ($notDone as $item) {
            $additional = $item->getAdditionalInformation(TransactionEntity::RAW_DETAILS);
            $incrementId = $additional[Transaction::INCREMENT_ID_KEY] ?? null;

            $message = isset($additional[Transaction::ERROR_MESSAGE_KEY])
                ? $additional[Transaction::ERROR_MESSAGE_KEY]
                : ($additional[Transaction::RAW_RESPONSE_KEY] ?? '');

            $message = wordwrap($message, 30);

            $detailedStat->addRow(
                [
                    $item->getCreatedAt(),
                    $item->getKkmStatus(),
                    $item->getTxnId(),
                    $item->getTxnType(),
                    $incrementId,
                    $message,
                ]
            );
            $detailedStat->addRow(new TableSeparator());
        }

        $commonStat->render();
        $detailedStat
            ->render();
    }
}
