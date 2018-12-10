<?php

namespace PlanBundle\Schedule\Template;

use PlanBundle\Entity\Zone;
use AppBundle\Doctrine\ValueObject\TimePeriod;
use PlanBundle\Schedule\Common\Resource\MemObserver;
use PlanBundle\Schedule\Common\Logger\SchedulerLogger;
use AppBundle\Exception\BusinessLogicException;
use PlanBundle\Schedule\Common\Resource\Timer;
use PlanBundle\Schedule\Template\Exception\InvalidSchedulePeriodException;

class TemplateSchedulerManager
{
    /**
     * @var TemplateSchedulerFactory
     */
    private $factory;

    /**
     * @var SchedulerLogger
     */
    private $logger;

    /**
     * @var AbstractTemplateScheduler
     */
    private $scheduler;

    /**
     * Create an instance of Manager.
     * Manager tasks:
     * - Get a scheduler
     * - Schedule in a period and zone
     * - Save results
     * - Create logs for events and results
     *
     * @param TemplateSchedulerFactory $factory
     * @param SchedulerLogger          $schedulerLogger
     */
    public function __construct(TemplateSchedulerFactory $factory, SchedulerLogger $schedulerLogger)
    {
        $this->factory = $factory;
        $this->logger = $schedulerLogger;
        $this->scheduler = null;
    }

    /**
     * Get Scheduler
     * @return AbstractTemplateScheduler
     */
    public function getScheduler()
    {
        return $this->scheduler;
    }

    /**
     * Get Manager of Registry From Factory.
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getManager()
    {
        return $this->factory->getRegistry()->getManager();
    }

    /**
     * PreCondition of Scheduling: TimePeriod is a maximum month-range period.
     *
     * Schedule templates in a Zone, and in a TimePeriod.
     * @param  Zone       $zone
     * @param  TimePeriod $period
     * @return \PlanBundle\Schedule\Template\TemplateSchedulerManager
     */
    public function schedule(Zone $zone, TimePeriod $period)
    {
        $timer = new Timer();
        $mem = new MemObserver();
        try {
            if ($this->isFullMonthSchedule($period)) {
                $this->clearShifts($zone, $period);
                $this->scheduler = $this->factory->getFullMonthTemplateScheduler($zone, $period);
                $this->scheduler->run();
            } else if ($this->isInCompleteMonthSchedule($period)) { //incomplete month scheduling
                $this->clearShifts($zone, $period);
                $this->scheduler = $this->factory->getIncompleteMonthTemplateScheduler($zone, $period);
                $this->scheduler->run();
            } else {
                throw new InvalidSchedulePeriodException('Schedule period shall not extends to the next month.');
            }
        } catch (\PlanBundle\Schedule\Template\Exception\MinimumAppearanceException $me) {
            $this->logger->initLog("TemplateScheduling Failed! Exception raised: " . $me->getMessage());
            throw $me;
        } catch (\PlanBundle\Schedule\Template\Exception\InvalidTemplateException $it) {
            $this->logger->initLog("TemplateScheduling Failed! Exception raised: " . $it->getMessage());
            throw $it;
        } catch (\PlanBundle\Schedule\Template\Exception\InvalidSchedulePeriodException $se) {
            $this->logger->initLog("TemplateScheduling Failed! Exception raised: " . $se->getMessage());
            throw $se;
        }

        $this->getManager()->getConnection()->setNestTransactionsWithSavepoints(true);
        $this->getManager()->getConnection()->beginTransaction();

        $this->logger->initLog("Templatescheduling: Before Save: RunTime: " . $timer->time() . " . Mem: " . $mem->mem() . " ");
        $timer->start();
        $mem->start(); //count again

        $results = $this->storeSchedule();
        $this->getManager()->flush();

        $logMsg = "TemplateScheduling comleted!" . count($results['errors']) . " error raised during saving (errors below), " . $results['pairedCount'] . " pairs were saved. Runtime: " . $timer->time() . ". Memory usage: " . $mem->mem() . " ";
        if (!empty($results['errors'])) {
            $logMsg .= "TemplateScheduling Errors: " . json_encode($results['errors']);
            $this->scheduler->getScheduleResult()->getSlaMessages()->set('save_errors', $results['responseErrors']);
            $this->scheduler->getScheduleResult()->removeUnSavedPairsFromResult(array_keys($results['responseErrors']));
        }

        $this->logger->initLog($logMsg);
        $this->getManager()->getConnection()->commit();

        return $this;
    }

    /**
     * Save scheduled Shifts.
     *
     * @return array
     */
    private function storeSchedule()
    {
        $pairedCount = 0;
        $errors = array();
        $responseErrors = array();
        foreach ($this->scheduler->getScheduleResult()->mergePairs() as $pair) {
            try {
                $shift = $this->getManager()->getRepository('PlanBundle:Shift')->find($pair->getId());
                $template = $this->getManager()->getRepository('PlanBundle:Template')->find($pair->getTemplate()->getId());
                $shift->setTemplate($template);
                $this->getManager()->persist($shift);

                $pairedCount++;
            } catch (BusinessLogicException $be) {
                $errors[$pair->getId()] = "BusinessLogicException: Cannot save shift #" . $pair->getId() . " with template #" . $pair->getTemplate()->getId() . " because of :" . $be->getMessage();
                $responseErrors[$pair->getId()] = "A #" . $pair->getId() . " azonosítójú műszak nem menthető! A szerver válasza: " . $be->getMessage();
            } catch (\Exception $e) {
                $errors[$pair->getId()] = "Cannot save shift #" . $pair->getId() . " with template #" . $pair->getTemplate()->getId() . " because of :" . $e->getMessage();
                $responseErrors[$pair->getId()] = "A #" . $pair->getId() . " azonosítójú műszak nem menthető! A szerver válasza: " . $e->getMessage();
            }
        }

        return array(
            "pairedCount" => $pairedCount,
            "errors" => $errors,
            "responseErrors" => $responseErrors
        );
    }

    /**
     * Function to decide scheduler type.
     *
     * @param  TimePeriod $period
     * @return boolean
     */
    private function isFullMonthSchedule(TimePeriod $period)
    {
        return $period->getStart() == new \DateTime($period->getStart()->format('Y-m-01 00:00:00')) && $period->getEnd() == $this->getFirstDayOfNextMonth($period->getStart());
    }

    private function isInCompleteMonthSchedule(TimePeriod $period)
    {
        return $period->getStart() >= new \DateTime($period->getStart()->format('Y-m-01 00:00:00')) && $period->getEnd() <= $this->getFirstDayOfNextMonth($period->getStart());
    }

    /**
     * Get the fist day of the determined datetime's month.
     *
     * @param  \DateTime $date
     * @return \DateTime
     */
    private function getFirstDayOfNextMonth(\DateTime $date)
    {
        $firstDayOfNextMonth = clone $date;
        $firstDayOfNextMonth->add(new \DateInterval('P1M'));
        return new \DateTime($firstDayOfNextMonth->format('Y-m-01 00:00:00'));
    }

    /**
     * Remove templates from the shift in period and zone.
     *
     * @param  Zone       $zone
     * @param  TimePeriod $period
     */
    private function clearShifts(Zone $zone, TimePeriod $period)
    {
        $sql = "UPDATE shift SET template_id=NULL WHERE zone_id=:zone AND is_fixed=0 AND is_local=0 AND active_from >= :start AND active_from < :end";
        $query = $this->getManager()->getConnection()->prepare($sql);
        $query->bindValue('zone', $zone->getId());
        $query->bindValue('start', $period->getStart()->format('Y-m-d H:i:s'));
        $query->bindValue('end', $period->getEnd()->format('Y-m-d H:i:s'));
        $query->execute();
        $this->getManager()->flush();
        $this->getManager()->clear();
    }
}
