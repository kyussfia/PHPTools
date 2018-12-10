<?php

namespace PlanBundle\Schedule\Template\Scheduler;

class IncompleteMonthTemplateScheduler extends \PlanBundle\Schedule\Template\AbstractTemplateScheduler
{
    public function getCalculatedMobileShiftNumber()
    {
        $num = $this->zone->getMobileShiftMax() - $this->prevDoneMobileShifts->count() - $this->fixedMobileShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function getCalculatedHighwayShiftNumber()
    {
        $num =  $this->zone->getHighwayShiftMax() - $this->prevDoneHighwayMobileShifts->count() - $this->fixedHighwayShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function getCalculatedGroupShiftNumber()
    {
        $num = $this->zone->getGroupShiftMax() - $this->prevDoneGroupShifts->count() - $this->fixedGroupShifts->count();
        return $num < 0 ? 0 : $num;
    }

    public function calculateMileageQuota()
    {
        $prevMileage = $this->calculateMileageOfPreviousDoneMobileShifts();
        $fixedMileage = $this->calculateMileageOfFixedMobileShifts();
        $scheduledMileage = $this->scheduleResult->calculateMobileMileageQuota();
        return $prevMileage + $fixedMileage + $scheduledMileage;   
    }

    public function calculateMileageQuotaWithResult($scheduleResult)
    {
        $prevMileage = $this->calculateMileageOfPreviousDoneMobileShifts();
        $fixedMileage = $this->calculateMileageOfFixedMobileShifts();
        $scheduledMileage = $scheduleResult->calculateMobileMileageQuota();
        return $prevMileage + $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMileageQuota()
    {
        $prevMileage = $this->calculateMileageOfPrevDoneGroupShifts();
        $fixedMileage = $this->calculateMileageOfFixedGroupShifts();
        $scheduledMileage = $this->scheduleResult->calculateMileageOfGroupShifts();
        return $prevMileage + $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMileageQuotaWithResult($scheduleResult)
    {
        $prevMileage = $this->calculateMileageOfPrevDoneGroupShifts();
        $fixedMileage = $this->calculateMileageOfFixedGroupShifts();
        $scheduledMileage = $scheduleResult->calculateMileageOfGroupShifts();
        return $prevMileage + $fixedMileage + $scheduledMileage;
    }

    public function calculateGroupMeasureRatioQuota()
    {
        $durations = 0;
        $prevMeasuredTime = $this->calculatePrevDoneGroupMeasureTime();
        $durations += $this->calculatePrevDoneGroupDuration();
        $fixedMeasureTime = $this->calculateFixedGroupMeasureTime();
        $durations += $this->calculateFixedGroupDuration();
        $scheduledMeasureTime = $this->scheduleResult->calculateGroupMeasureTime();
        $durations += $this->scheduleResult->calculateGroupDuration();

        return $durations > 0 ? round(($prevMeasuredTime + $fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateGroupMeasureRatioQuotaWithResult($scheduleResult)
    {
        $durations = 0;
        $prevMeasuredTime = $this->calculatePrevDoneGroupMeasureTime();
        $durations += $this->calculatePrevDoneGroupDuration();
        $fixedMeasureTime = $this->calculateFixedGroupMeasureTime();
        $durations += $this->calculateFixedGroupDuration();
        $scheduledMeasureTime = $scheduleResult->calculateGroupMeasureTime();
        $durations += $scheduleResult->calculateGroupDuration();

        return $durations > 0 ? round(($prevMeasuredTime + $fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateMeasureRatioQuota()
    {
        $durations = 0;
        $prevMeasuredTime = $this->calculatePrevDoneMobileMeasureTime();
        $durations += $this->calculatePrevDoneMobileDuration();
        $fixedMeasureTime = $this->calculateFixedMobileMeasureTime();
        $durations += $this->calculateFixedMobileDuration();
        $scheduledMeasureTime = $this->scheduleResult->calculateMobileMeasureTime();
        $durations += $this->scheduleResult->calculateMobileDuration();

        return $durations > 0 ? round(($prevMeasuredTime + $fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

    public function calculateMeasureRatioQuotaWithResult($scheduleResult)
    {
        $durations = 0;
        $prevMeasuredTime = $this->calculatePrevDoneMobileMeasureTime();
        $durations += $this->calculatePrevDoneMobileDuration();
        $fixedMeasureTime = $this->calculateFixedMobileMeasureTime();
        $durations += $this->calculateFixedMobileDuration();
        $scheduledMeasureTime = $scheduleResult->calculateMobileMeasureTime();
        $durations += $scheduleResult->calculateMobileDuration();

        return $durations > 0 ? round(($prevMeasuredTime + $fixedMeasureTime + $scheduledMeasureTime)*100 / $durations) : 0;
    }

	public function calculateMileageOfPreviousDoneMobileShifts()
	{
		$sum = 0;
		foreach ($this->prevDoneMobileShifts as $key => $shift) {
			$sum += $shift->getTemplate() ? $shift->getTemplate()->getMileage() : 0;
		}
		return $sum;
	}

	public function calculateMileageOfPrevDoneGroupShifts()
	{
		$sum = 0;
		foreach ($this->prevDoneGroupShifts as $key => $shift) {
			$sum += $shift->getTemplate() ? $shift->getTemplate()->getMileage() : 0;
		}
		return $sum;
	}

	public function calculatePrevDoneMobileMeasureTime()
	{
		$time = 0;
		foreach ($this->prevDoneMobileShifts as $shift) {
			$time += $shift->getTemplate() ? $shift->getTemplate()->getMeasuredTime() : 0;
		}
		return $time;
	}

	public function calculatePrevDoneMobileDuration()
	{
		$duration = 0;
		foreach ($this->prevDoneMobileShifts as $shift) {
			$duration += $shift->getTemplate() ? $shift->getTemplate()->getDuration() : 0;
		}
		return $duration;
	}

	public function calculatePrevDoneGroupMeasureTime()
	{
		$time = 0;
		foreach ($this->prevDoneGroupShifts as $shift) {
			$time += $shift->getTemplate() ? $shift->getTemplate()->getMeasuredTime() : 0;
		}
		return $time;
	}

	public function calculatePrevDoneGroupDuration()
	{
		$duration = 0;
		foreach ($this->prevDoneGroupShifts as $shift) {
			$duration += $shift->getTemplate() ? $shift->getTemplate()->getDuration() : 0;
		}
		return $duration;
	}
}
