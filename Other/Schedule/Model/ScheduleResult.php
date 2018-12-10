<?php

namespace PlanBundle\Schedule\Template\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * ScheduleResult
 */
class ScheduleResult
{
    protected $nonHighwayPairs;

    protected $highwayPairs;

    protected $groupPairs;

    private $alreadyPairedShiftsNotInZone;

    private $fixedShiftsInZone;

    private $borderMobileShifts;

    private  $borderGroupShifts;

    protected $slaMessages;

    protected $unsavedPairs;

    public function __construct(array $alreadyPairedShiftsNotInZone, ArrayCollection $fixedShiftsInZone, $borderMobileShifts, $borderGroupShifts)
    {
        $this->nonHighwayPairs = new ArrayCollection();
        $this->highwayPairs = new ArrayCollection();
        $this->groupPairs = new ArrayCollection();
        $this->slaMessages = new ArrayCollection();
        $this->unsavedPairs = array();
        $this->alreadyPairedShiftsNotInZone = $alreadyPairedShiftsNotInZone;
        $this->fixedShiftsInZone = $fixedShiftsInZone;
        $this->borderMobileShifts = $borderMobileShifts;
        $this->borderGroupShifts = $borderGroupShifts;
        $this->slaMessages->set('mobil_shifts_reason', '');
        $this->slaMessages->set('mobile_hd_shifts_reason', '');
        $this->slaMessages->set('mobile_hd_ratio_reason', '');
        $this->slaMessages->set('mobile_mileage_quota_reason', '');
        $this->slaMessages->set('mobile_measure_ratio_reason', '');
        $this->slaMessages->set('group_shifts_reason', '');
        $this->slaMessages->set('group_mileage_quota_reason', '');
        $this->slaMessages->set('group_measure_ratio_reason', '');
        $this->slaMessages->set('minimum_location_appearances_reason', ''); // uj
        $this->slaMessages->set('save_errors', array()); // uj
        $this->slaMessages->set('group_night_shifts_reason', '');
        $this->slaMessages->set('group_weekend_shifts_reason', '');
    }

    public function getFixedShiftsInZone()
    {
        return $this->fixedShiftsInZone;
    }

    public function getFixedShiftsInZoneByPeriod(\AppBundle\Doctrine\ValueObject\TimePeriod $period)
    {
        return $this->fixedShiftsInZone->filter(
            function($shift) use ($period) {
                return $period->overlaps($shift->getActivityPeriod());
        });
    }

    public function getMobileBorderShiftsInPeriod(\AppBundle\Doctrine\ValueObject\TimePeriod $period)
    {
        return $this->borderMobileShifts->filter(
            function($shift) use ($period)
            {
                return $period->overlaps($shift->getActivityPeriod());
            }
        );
    }

    public function getBorderShiftsArray()
    {
        return new ArrayCollection(array_merge($this->borderMobileShifts->toArray(), $this->borderGroupShifts->toArray()));
    }

	/**
	 * Get not in Zone shifts relevant to the given shift.
	 * @param integer $shiftId
	 * @return mixed
	 */
    public function getNotInZoneShiftsByShiftId($shiftId)
    {
    	return $this->alreadyPairedShiftsNotInZone[$shiftId];
    }

	public function getMobilePairsByPeriod(\AppBundle\Doctrine\ValueObject\TimePeriod $period)
	{
		return $this->getMobilePairs()->filter(
			function($shift) use ($period) {
				return $period->overlaps($shift->getActivityPeriod());
			});
	}

    public function getSlaMessages()
    {
        return $this->slaMessages;
    }

    public function getUnsavedPairs()
    {
        return $this->unsavedPairs;
    }

    public function getHighwayPairs()
    {
        return $this->highwayPairs;
    }

    public function setHighwayPairs(ArrayCollection $highwayPairs)
    {
        $this->highwayPairs = $highwayPairs;

        return $this;
    }

	public function getHighwayPairShiftIdsArray()
	{
		$shiftIds = array();
		foreach ($this->highwayPairs as $pair) {
			$shiftIds[] = $pair->getId();
		}
		return $shiftIds;
	}

    public function getNonHighwayPairs()
    {
        return $this->nonHighwayPairs;
    }

    public function setNonHighwayPairs(ArrayCollection $nonHighwayPairs)
    {
        $this->nonHighwayPairs = $nonHighwayPairs;

        return $this;
    }

    public function getMobilePairs()
    {
        return new ArrayCollection(array_merge($this->nonHighwayPairs->toArray(), $this->highwayPairs->toArray()));
    }

    public function getGroupPairs()
    {
        return $this->groupPairs;
    }

    public function setGroupPairs(ArrayCollection $pairs)
    {
        $this->groupPairs = $pairs;

        return $this;
    }

    public function mergePairs()
    {
        return new ArrayCollection(array_merge($this->nonHighwayPairs->toArray(), $this->highwayPairs->toArray(), $this->groupPairs->toArray()));
    }

    public function toArray()
    {
        return $this->mergePairs()->toArray();
    }

    public function removeUnSavedPairsFromResult(array $pairIds)
    {
        foreach ($this->nonHighwayPairs as $pair) {
            if (in_array($pair->getId(), $pairIds)) {
                $this->unsavedPairs[] = $pair->getId();
                $this->nonHighwayPairs->removeElement($pair);
            }
        }
        foreach ($this->highwayPairs as $pair) {
            if (in_array($pair->getId(), $pairIds)) {
                $this->unsavedPairs[] = $pair->getId();
                $this->highwayPairs->removeElement($pair);
            }
        }
        foreach ($this->groupPairs as $pair) {
            if (in_array($pair->getId(), $pairIds)) {
                $this->unsavedPairs[] = $pair->getId();
                $this->groupPairs->removeElement($pair);
            }
        }
    }

    public function getSortedHighwayPairsByMileage()
    {
        $mobilePairs = $this->highwayPairs->toArray();
        usort($mobilePairs, array('self', 'mileageCompare'));

        return $mobilePairs;
    }

    public function getSortedNonHighwayPairsByMileage()
    {
        $mobilePairs = $this->nonHighwayPairs->toArray();
        usort($mobilePairs, array('self', 'mileageCompare'));

        return $mobilePairs;
    }

    public function getSortedGroupPairsByMeasureRatio()
    {
        $pairs = $this->groupPairs->toArray();
        usort($pairs, array('self', 'mileageCompare'));

        return $pairs;
    }

    private static function mileageCompare($a, $b)
    {
        if ($a->getTemplate()->getMileage() == $b->getTemplate()->getMileage()) {
            return 0;
        }
        return ($a->getTemplate()->getMileage() < $b->getTemplate()->getMileage()) ? 1 : -1;
    }

    public function getSortedHighwayPairsByMeasureRatio()
    {
        $mobilePairs = $this->highwayPairs->toArray();
        usort($mobilePairs, array('self', 'measureRatioCompare'));

        return $mobilePairs;
    }

    public function getSortedNonHighwayPairsByMeasureRatio()
    {
        $mobilePairs = $this->nonHighwayPairs->toArray();
        usort($mobilePairs, array('self', 'measureRatioCompare'));

        return $mobilePairs;
    }

    public function getSortedGroupPairsByMileage()
    {
        $pairs = $this->groupPairs->toArray();
        usort($pairs, array('self', 'measureRatioCompare'));

        return $pairs;
    }

    private static function measureRatioCompare($a, $b)
    {
        if ($a->getTemplate()->getMeasuredTime() == $b->getTemplate()->getMeasuredTime()) {
            return 0;
        }
        return ($a->getTemplate()->getMeasuredTime() < $b->getTemplate()->getMeasuredTime()) ? -1 : 1;
    }

    public function calculateMobileMeasureTime()
    {
        $time = 0;
        foreach ($this->getMobilePairs() as $mobilePair) {
            $time += $mobilePair->getTemplate()->getMeasuredTime();
        }

        return $time;
    }

    public function calculateMobileDuration()
    {
        $allDuration = 0;
        foreach ($this->getMobilePairs() as $mobilePair) {
            $allDuration += $mobilePair->getTemplate()->getDuration();
        }
        return $allDuration;
    }

    public function calculateGroupMeasureTime()
    {
        $time = 0;
        foreach ($this->getGroupPairs() as $pair) {
            $time += $pair->getTemplate()->getMeasuredTime();
        }

        return $time;
    }

    public function calculateGroupDuration()
    {
        $allDuration = 0;
        foreach ($this->getGroupPairs() as $pair) {
            $allDuration += $pair->getTemplate()->getDuration();
        }
        return $allDuration;
    }

    public function calculateMobileMileageQuota()
    {
        $sum = 0;
        foreach ($this->getMobilePairs() as $mobilePair) {
            $sum += $mobilePair->getTemplate()->getMileage();
        }
        return $sum;
    }

    public function calculateMileageOfGroupShifts()
    {
        $sum = 0;
        foreach ($this->getGroupPairs() as $pair) {
            $sum += $pair->getTemplate()->getMileage();
        }
        return $sum;
    }
}