<?php

namespace App\Command;

use App\Entity\ExchangeRate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCurrencyCommand extends ContainerAwareCommand
{
    const filePrefix = 'update_currency';
    private $logMessage = '';
    private $logFileDir = __DIR__ . '/../../var/log/Command';
    private $logFileName = '/'. self::filePrefix . '_error.log';
    private $successLogFileName = '/' . self::filePrefix . '_history.log';
    private $catchExceptions = true;

    protected function configure()
    {
        $this
            ->setName('app:update:currency')
            ->setDescription('Updating the currency table with up to date data from the exchange rate table.')
            ->setHelp('app:update:currency - This command allows you to update currency table with data from exchange rate table.'.PHP_EOL.'This requires no additional parameters.')
        ;

        $this->addArgument('catch_exceptions', InputArgument::OPTIONAL, 'On standalone running it msut be true, otherwise (e.g.: Call from control command) must be false!');
    }

    private function handleArgument(InputInterface $input)
    {
        if (null !== $input->getArgument('catch_exceptions'))
        {
            $this->catchExceptions = $input->getArgument('catch_exceptions');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleArgument($input);
        $output->writeln([
            '=======================================================',
            'Currency Table updater (Update data from Exchange Rate)',
            '=======================================================',
        ]);
        $output->writeln('Get last data from exchange_rate...');
        $this->logMessage .= PHP_EOL . '['.date('Y-m-d H:i:s').'] INFO: ' ;
        try {
            $lastEx = $this->getLastExchangeRate();
            if (null === $lastEx)
            {
                $output->writeln('<question>There is no data at exchange_rate, nothing to update with.</question>');
                $this->logMessage .= 'There is no data at exchange_rate, nothing to update with.';
            } else {
                $currencies = $this->getLastCurrencies();
                if (!$this->isUpToDate($currencies, $lastEx))
                {
                    if (empty($currencies))
                    {
                        $output->writeln('<error>Missing predefined Currency Entities: EUR, USD. Please create them manually.</error>');
                        $this->logMessage .= 'Missing predefined Currency Entities: EUR, USD. Please create them manually.';
                        throw new \InvalidArgumentException("Missing predefined Currency Entities: EUR, USD. Please create them manually.");
                    }
                    $output->writeln('Update currency table.');
                    $this->update($currencies, $lastEx);
                    $this->logMessage .=  'Data in currency table is updated.';
                    $output->writeln('<info>Done</info>');
                } else {
                    $output->writeln('<info>There is no new data at exchange rate table. Currency is up to date.</info>');
                    $this->logMessage .= 'There is no new data at exchange rate table. Currency is up to date.';
                }
            }
            $this->logToFile($this->successLogFileName, $this->logMessage);
        } catch (\Exception $e)
        {
            //create log
            $logMessage = PHP_EOL . '['.date('Y-m-d H:i:s').'] ERROR: ' . $e->getMessage() . ' (at line: '.$e->getLine().') Trace: ' . $e->getTraceAsString() . ' Created history log: '. $this->logMessage . PHP_EOL;
            $output->writeln('<error>'.$logMessage.'</error>');
            $this->logToFile($this->logFileName, $logMessage);
            if (!$this->catchExceptions)
            {
                throw $e;
            }
        }
    }

    private function logToFile(string $logfile, string $log)
    {
        if (!is_dir($this->logFileDir))
        {
            mkdir($this->logFileDir, 0777, true);
        }
        file_put_contents($this->logFileDir . $logfile, $log, FILE_APPEND);
    }

    private function update(array $currencies, ExchangeRate $last)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        ($currencies['EUR'])->setRateInHuf($last->getEurInHuf());
        ($currencies['USD'])->setRateInHuf($last->getUsdInHuf());
        $em->persist($currencies['EUR']);
        $em->persist($currencies['USD']);
        $em->flush();
    }

    private function isUpToDate(array $stored, ExchangeRate $fresh) : bool
    {
        return !empty($stored) && ($stored['EUR'])->getRateInHuf() == $fresh->getEurInHuf()  && ($stored['USD'])->getRateInHuf() == $fresh->getUsdInHuf();
    }

    /**
     * Result array is assoc keyed by currency codes (id).
     *
     * @return array
     */
    private function getLastCurrencies() : array
    {
        $result = array();
        $queryResult = $this->getContainer()->get('doctrine')->getEntityManager()->getRepository('App:Currency')->findAll();
        array_walk($queryResult, function ($e) use (&$result) {
            $result[$e->getCurrencyCode()] = $e;
        });
        return $result;
    }


    private function getLastExchangeRate() : ?ExchangeRate
    {
        return $this->getContainer()->get('doctrine')->getEntityManager()->getRepository('App:ExchangeRate')->findOneBy(array(), array('createdAt' => 'DESC'));
    }
}