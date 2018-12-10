<?php

namespace App\Command;

use App\Entity\ExchangeRate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Util\Mnb\MnbSoapApi;

class UpdateExchangeRateHistoryCommand extends ContainerAwareCommand
{
    private $logMessage = '';
    private $data; //Pre-created array by MnbSoapApi. Initialized on downloadData method.
    const filePrefix = 'update_exchange_rate';
    private $logFileDir = __DIR__ . '/../../var/log/Command';
    private $logFileName = '/'. self::filePrefix . '_error.log';
    private $successLogFileName = '/' . self::filePrefix . '_history.log';
    private $catchExceptions = true;

    protected function configure()
    {
        $this
            ->setName('app:update:exchange_rate')
            ->setDescription('Downloading current exchange rate from MNB and store it in the database ()')
            ->setHelp('app:update:exchange_rate - This command allows you to update exchange rate table with the current data get from MNB via Soap api.'.PHP_EOL.'This requires no additional parameters.')
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
            '=========================',
            'MNB Exchange rate updater',
            '=========================',
        ]);
        $output->writeln('Downloading actual data from Mnb Soap Server.');
        try {
            $this->downloadData();
            $this->logMessage .= PHP_EOL . '['.date('Y-m-d H:i:s').'] INFO: ' ;

            if (!$this->isUpToDate())
            {
                $output->writeln('Insert data into database.');
                $this->update();
                $this->logMessage .= 'New data is available at MNB. Insert into database';
                $output->writeln('<info>Done</info>');
            } else {
                $output->writeln('<info>There is no new available data at MNB-side. Exchange rate is up to date.</info>');
                $this->logMessage .= 'There is no new available data at MNB-side. Exchange rate is up to date. ';
            }
            $this->logToFile($this->successLogFileName, $this->logMessage);
        } catch (\Exception $e) {
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

    private function update()
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $validAt = new \DateTime($this->data['date']); //can throw exception on bad input data ?
        $createdAt = new \DateTime();
        $eurInHuf = $this->getCurrencyAsFloat('EUR');
        $usdInHuf = $this->getCurrencyAsFloat('USD');
        $new = new ExchangeRate($validAt, $createdAt, $eurInHuf, $usdInHuf);
        $em->persist($new);
        $em->flush();
    }

    private function downloadData()
    {
        $api = new MnbSoapApi();
        $this->data = $api->getCurrentExchangeRates(array('curr' => array("EUR", "USD")));
        $this->check();
    }

    /**
     * Is the Insertion to the exchange_rate table required?
     *
     * @return bool
     */
    private function isUpToDate() : bool
    {
        $last = $this->getLast();
        if (null === $last) //no data in db
        {
            return false;
        }
        return strtotime($this->data['date']) <= $last->getValidAt()->getTimestamp();
    }

    /**
     * Perform a quick key exist check on downloaded data.
     */
    private function check()
    {
        if (!array_key_exists('date', $this->data))
        {
            throw new \InvalidArgumentException('Date key of the response data is not exists. Unable to determine actual validity date.');
        }

        if (!array_key_exists('data', $this->data))
        {
            throw new \InvalidArgumentException('Data key of the response data is not exists. Unable to determine actual content.');
        }

        if (!array_key_exists('EUR', $this->data['data']))
        {
            throw new \InvalidArgumentException('Missing EUR key at downloaded content.');
        }

        if (!array_key_exists('USD', $this->data['data']))
        {
            throw new \InvalidArgumentException('Missing USD key at downloaded content.');
        }
    }

    /**
     * @return \App\Entity\ExchangeRate|null
     */
    private function getLast() : ?\App\Entity\ExchangeRate
    {
        return $this->getContainer()->get('doctrine')->getEntityManager()->getRepository('App:ExchangeRate')->findOneBy(array(), array('createdAt' => 'DESC'));
    }

    /**
     * $this->data array contains string floats at EUR and USD keys.
     * The reprezentation contains a unit number, which means X unit HUF equals to the given currency (at nodeValue). So we need to divide it to gain the current currency value.
     * Because of the Dom representation there is a nodeValue key too.
     *
     * @param string $key
     * @return float
     */
    private function getCurrencyAsFloat(string $key)
    {
        $value = $this->stringToFloat($this->data['data'][$key]['nodeValue']);
        $unit = $this->stringToFloat($this->data['data'][$key]['unit']);
        return $value / $unit;
    }

    private function stringToFloat(string $number) : float
    {
        return floatval(str_replace(',', '.', $number));
    }
}