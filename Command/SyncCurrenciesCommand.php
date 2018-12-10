<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCurrenciesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:sync:currencies')
            ->setDescription('Call the exchange rate updater command adn then the currency updater command, to synchronize actual data with the application database. This should be called from cron or manually.')
            ->setHelp('app:sync:currencies - This command allows you to update currency table with the real data from an external api. This command calls the UpdateExchangeRateHistoryCommand then the UpdateCurrencyCommand.'.PHP_EOL.'This requires no additional parameters.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '======================',
            'Synchronize Currencies',
            '======================',
        ]);
        $baseInput = new ArrayInput(array(
            'catch_exceptions' => false
        ));
        $output->writeln('Call the UpdateExchangeRateHistoryCommand, to get the current rates, to the exchange_rate table.');
        try {
            $exCommand = $this->getApplication()->find('app:update:exchange_rate');
            $exResult = $exCommand->run($baseInput, $output);
            if (0 == $exResult) {
                $output->writeln('<info>Command exited successfully!</info>');
            }
            $currCommand = $this->getApplication()->find('app:update:currency');
            $currResult = $currCommand->run($baseInput, $output);
            if (0 == $currResult) {
                $output->writeln('<info>Command exited successfully!</info>');
            }
        } catch (\Exception $e) {
            $logMessage = PHP_EOL . '['.date('Y-m-d H:i:s').'] ERROR: ' . $e->getMessage() . ' (at line: '.$e->getLine().') Trace: ' . $e->getTraceAsString() . PHP_EOL;
            if (!$this->sendMail($logMessage)) //error on mailing -> log to file
            {
                $logMessage .= 'Email send has failed!';
            }
            $output->writeln('<error>'.$logMessage.'</error>');
        }
    }

    private function sendMail($message)
    {
        $msg = new \Swift_Message();
        $msg
            ->setFrom($this->getContainer()->getParameter('mailer_sender_address'))
            ->setTo($this->getContainer()->getParameter('monolog.to_mail'))
            ->setSubject('Error during Synchronize Currencies.')
            ->setBody($message)
        ;
        $mailer = $this->getContainer()->get('mailer');
        return $mailer->send($msg);
    }
}