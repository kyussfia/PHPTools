<?php

namespace PlanBundle\Schedule\Common\Logger;

use Symfony\Bridge\Monolog\Logger as Monolog;

class SchedulerLogger
{
    private $logger;

    private $options;

    public function __construct(Monolog $monolog, array $options = array())
    {
        $this->logger = $monolog;
        $this->options = $options;
    }

    public function getLogLevel()
    {
        return $this->options['logLevel'];
    }

    public function initLog($logMsg)
    {
        switch ($this->getLogLevel()) {
            case 'emergency': // 600
                $this->logger->emergency($logMsg);
                break;
            case 'alert': //550
                $this->logger->alert($logMsg);
                break;
            case 'critical': //500
                $this->logger->critical($logMsg);
                break;
            case 'error': //400
                $this->logger->error($logMsg);
                break;
            case 'warning': //300
                $this->logger->warning($logMsg);
                break;
            case 'notice': // 250
                $this->logger->notice($logMsg);
                break;
            case 'info': //200
                $this->logger->info($logMsg);
                break;
            case 'debug': //100
            default:
                $this->logger->debug($logMsg);
                break;
        }
    }    
}