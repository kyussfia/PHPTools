<?php

namespace PlanBundle\Schedule\Template\Scheduler;

class FullMonthTemplateScheduler extends \PlanBundle\Schedule\Template\AbstractTemplateScheduler
{
    public function getCalculatedMobileShiftNumber()
    {
        $num = $this->zone->getMobileShiftMax() - $this->fixedMobileShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function getCalculatedHighwayShiftNumber()
    {
        $num = $this->zone->getHighwayShiftMax() - $this->fixedHighwayShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function getCalculatedGroupShiftNumber()
    {
        $num = $this->zone->getGroupShiftMax() - $this->fixedGroupShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function calculateMileageQuota()
    {
        $fixedMileage = $this->calculateMileageOfFixedMobileShifts();
        $scheduledMileage = $this->scheduleResult->calculateMobileMileageQuota();
        return $fixedMileage + $scheduledMileage;   
    }

    public function calculateMileageQuotaWithResult($scheduleResult)
    {
        $fixedMileage = $this->calculateMileageOfFixedMobileShifts();
        $scheduledMileage = $scheduleResult->calculateMobileMileageQuota();
        return $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMileageQuota()
    {
        $fixedMileage = $this->calculateMileageOfFixedGroupShifts();
        $scheduledMileage = $this->scheduleResult->calculateMileageOfGroupShifts();
        return $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMileageQuotaWithResult($scheduleResult)
    {
        $fixedMileage = $this->calculateMileageOfFixedGroupShifts();
        $scheduledMileage = $scheduleResult->calculateMileageOfGroupShifts();
        return $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMeasureRatioQuota()
    {
        $durations = 0;
        $fixedMeasureTime = $this->calculateFixedGroupMeasureTime();
        $durations += $this->calculateFixedGroupDuration();
        $scheduledMeasureTime = $this->scheduleResult->calculateGroupMeasureTime();
        $durations += $this->scheduleResult->calculateGroupDuration();

        return $durations > 0 ? round(($fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateGroupMeasureRatioQuotaWithResult($scheduleResult)
    {
        $durations = 0;
        $fixedMeasureTime = $this->calculateFixedGroupMeasureTime();
        $durations += $this->calculateFixedGroupDuration();
        $scheduledMeasureTime = $scheduleResult->calculateGroupMeasureTime();
        $durations += $scheduleResult->calculateGroupDuration();

        return $durations > 0 ? round(($fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateMeasureRatioQuota()
    {
        $durations = 0;
        $fixedMeasureTime = $this->calculateFixedMobileMeasureTime();
        $durations += $this->calculateFixedMobileDuration();
        $scheduledMeasureTime = $this->scheduleResult->calculateMobileMeasureTime();
        $durations += $this->scheduleResult->calculateMobileDuration();

        return $durations > 0 ? round(($fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateMeasureRatioQuotaWithResult($scheduleResult)
    {
        $durations = 0;
        $fixedMeasureTime = $this->calculateFixedMobileMeasureTime();
        $durations += $this->calculateFixedMobileDuration();
        $scheduledMeasureTime = $scheduleResult->calculateMobileMeasureTime();
        $durations += $scheduleResult->calculateMobileDuration();

        return $durations > 0 ? round(($fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }
}
