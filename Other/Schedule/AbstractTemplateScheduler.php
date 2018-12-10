<?php

namespace PlanBundle\Schedule\Template;

use AppBundle\Doctrine\ValueObject\TimePeriod;
use Doctrine\Common\Collections\ArrayCollection;
use PlanBundle\Entity\Zone;
use PlanBundle\Entity\Shift;
use PlanBundle\Schedule\Template\Configuration\ShiftConfiguration;
use PlanBundle\Schedule\Template\Configuration\TemplateConfiguration;
use PlanBundle\Schedule\Template\Comparator\TemplateShiftComparator;

use PlanBundle\Schedule\Template\Model\TemplateShift;
use PlanBundle\Schedule\Template\Model\ScheduleResult;

use PlanBundle\Schedule\Template\Optimalizer\MileageOptimalizer;
use PlanBundle\Schedule\Template\Optimalizer\MeasureRatioOptimalizer;

use PlanBundle\Schedule\Common\Resource\Timer;

abstract class AbstractTemplateScheduler
{
    /**
     * @var \PlanBundle\Entity\Zone
     */
    protected $zone;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $baseHighwayTemplates;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $baseNonHighwayTemplates;

    /**
     * @var array
     */
    protected $truckBanShifts;

    /**
     * @var array
     */
    protected $availableMobileShifts;

    /**
     * @var array
     */
    protected $availableGroupShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $prevDoneMobileShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $prevDoneHighwayMobileShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $prevDoneGroupShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $fixedMobileShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $fixedHighwayShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $fixedGroupShifts;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $highwayTemplates;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $motorwayTemplates;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $groupTemplates;

    /**
     * @var TimePeriod
     */
    protected $period;

    /**
     * @var ShiftConfiguration
     */
    protected $shiftConfig;

    /**
     * @var TemplateConfiguration
     */
    protected $templateConfig;
    /**
     * @var ScheduleResult
     */
    protected $scheduleResult;

    /**
     * @var int
     */
    protected $targetHighwayNumber;

    /**
     * @var int
     */
    protected $targetMobileNumber;

    /**
     * @var array
     */
    private $optimalizationResult;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $misMeasureOptimalizedTemplates;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $misMileageOptimalizedTemplates;

    public function __construct(
        Zone $zone,
        ArrayCollection $baseHighwayTemplates,
        ArrayCollection $baseNonHighwayTemplates,
        array $truckBanShifts,
        array $availableMobileShifts,
        array $availableGroupShifts,
        array $alreadyPairedShiftsNotInZone,
        ArrayCollection $borderMobileShifts,
        ArrayCollection $borderGroupShifts,
        ArrayCollection $prevDoneMobileShifts,
        ArrayCollection $prevDoneHighwayMobileShifts,
        ArrayCollection $prevDoneGroupShifts,
        ArrayCollection $fixedMobileShifts,
        ArrayCollection $fixedHighwayShifts,
        ArrayCollection $fixedGroupShifts,
        ArrayCollection $highwayTemplates,
        ArrayCollection $motorwayTemplates,
        ArrayCollection $groupTemplates,
        TimePeriod $period,
        ShiftConfiguration $shiftConfig,
        TemplateConfiguration $templateConfig
    )
    {
	    $this->zone = $zone;

	    $this->truckBanShifts = $truckBanShifts;
	    $this->availableMobileShifts = $availableMobileShifts;
	    $this->availableGroupShifts = $availableGroupShifts;

	    $this->baseHighwayTemplates = $baseHighwayTemplates;
	    $this->baseNonHighwayTemplates = $baseNonHighwayTemplates;

	    $this->misMeasureOptimalizedTemplates = new ArrayCollection();
        $this->misMileageOptimalizedTemplates = new ArrayCollection();

        $this->prevDoneMobileShifts = $prevDoneMobileShifts;
        $this->prevDoneHighwayMobileShifts = $prevDoneHighwayMobileShifts;
        $this->prevDoneGroupShifts = $prevDoneGroupShifts;

        $this->fixedMobileShifts = $fixedMobileShifts;
        $this->fixedHighwayShifts = $fixedHighwayShifts;
        $this->fixedGroupShifts = $fixedGroupShifts;

        $this->highwayTemplates = $highwayTemplates;
        $this->motorwayTemplates = $motorwayTemplates;
        $this->groupTemplates = $groupTemplates;

        $this->period = $period;
        $this->shiftConfig = $shiftConfig;
        $this->templateConfig = $templateConfig;

        $this->scheduleResult = new ScheduleResult($alreadyPairedShiftsNotInZone, new ArrayCollection(array_merge($this->fixedMobileShifts->toArray(), $this->fixedHighwayShifts->toArray())), $borderMobileShifts, $borderGroupShifts);

        $this->targetHighwayNumber = $this->gearTargetHighwayShiftNumber();
        $this->targetMobileNumber = $this->gearTargetMobileShiftNumber();
    }

    public function getScheduleResult()
    {
        return $this->scheduleResult;
    }

    public function setScheduleResult($result)
    {
        $this->scheduleResult = $result;
        return $this;
    }

    public function getShiftConfig()
    {
        return $this->shiftConfig;
    }

    public function getTemplateConfig()
    {
        return $this->templateConfig;
    }

    public function getZone()
    {
        return $this->zone;
    }

    public function getBaseHighwayTemplates()
    {
        return $this->baseHighwayTemplates;
    }

    public function getBaseNonHighwayTemplates()
    {
        return $this->baseNonHighwayTemplates;
    }

    public function getRemainedBaseTemplatePairs()
    {
        $remains = new ArrayCollection();
        $merged = array_merge($this->baseHighwayTemplates->toArray(), $this->baseNonHighwayTemplates->toArray());
        foreach ($merged as $template) {
            $remains->set($template->getId(), "base");
        }
        return $remains;
    }

    public function getHighwayTemplates()
    {
        return $this->highwayTemplates;
    }

    public function getNonHighwayTemplates()
    {
        return $this->motorwayTemplates;
    }

    public function getGroupTemplates()
    {
        return $this->groupTemplates;
    }

    public function getOptimalizationResult()
    {
        return $this->optimalizationResult;
    }

    public function setMisMileageOptimalizedTemplates(ArrayCollection $pairs)
    {
        $this->misMileageOptimalizedTemplates = $pairs;
    }

    public function getMisMileageOptimalizedTemplates()
    {
        return $this->misMileageOptimalizedTemplates;
    }

    public function setMisMeasureOptimalizedTemplates(ArrayCollection $pairs)
    {
        $this->misMeasureOptimalizedTemplates = $pairs;
    }

    public function getMisMeasureOptimalizedTemplates()
    {
        return $this->misMeasureOptimalizedTemplates;
    }

    abstract public function getCalculatedMobileShiftNumber();

    abstract public function getCalculatedHighwayShiftNumber();

    abstract public function getCalculatedGroupShiftNumber();

    /**For KPI calculation**/
    abstract public function calculateMileageQuota();

    abstract public function calculateMeasureRatioQuota();

    abstract public function calculateGroupMileageQuota();

    private function gearTargetHighwayShiftNumber()
    {
        if ($this->getCalculatedMobileShiftNumber() > count($this->availableMobileShifts)) {
            $this->scheduleResult->getSlaMessages()->set('mobil_shifts_reason', "Nincs elég műszak, a HD műszakok száma kerekítésre került.");
            return floor(count($this->availableMobileShifts) * $this->getCalculatedHighwayShiftNumber() / $this->getCalculatedMobileShiftNumber());
        } elseif ($this->getCalculatedMobileShiftNumber() < count($this->availableMobileShifts)) {
            return floor(count($this->availableMobileShifts) * $this->getCalculatedHighwayShiftNumber() / $this->getCalculatedMobileShiftNumber());
        }
        return $this->getCalculatedHighwayShiftNumber();
    }

    private function gearTargetMobileShiftNumber()
    {
        return count($this->availableMobileShifts);
    }

    public function getTargetHighwayShiftNumber()
    {
        return $this->targetHighwayNumber;
    }

    public function getTargetMobileShiftNumber()
    {
        return $this->targetMobileNumber;
    }

    public function getTargetGroupShiftNumber()
    {
        if ($this->getCalculatedGroupShiftNumber() > count($this->availableGroupShifts)) {
            $this->scheduleResult->getSlaMessages()->set('group_shifts_reason', "Nincs elég csoportos műszak.");
        }
        return count($this->availableGroupShifts);
    }

    public function run()
    {
        $this->runMobileSchedule();
        $this->runGroupSchedule();
        $this->runOptimalizer();
        return $this;
    }

    private function runMobileSchedule()
    {
        $this->runTruckBanScheduleOnBaseHighways();
        $this->runTruckBanSchedule();
        $this->removeAlreadyPairedShiftsFromAvailableShifts();
        $this->runBaseHighwaySchedule();
        $this->runHighwaySchedule();
        $this->runBaseNonHighwaySchedule();
        $this->runNonHighwaySchedule();
    }
    private function runOptimalizer()
    {
        $this->optimalizationResult = array(
            'mileage' => array(),
            'measureRatio' => array()
        );
        $this->runMileageOptimalizer();
        $this->runMeasureRatioOptimalizer();
    }

    private function runGroupSchedule()
    {
        $this->runGroupShiftSchedule();
    }

	/**
	 * Scheduling  BaseTemplates On TruckBan Shifts:
	 * - Select shift From TruckbanShifts
	 * - With LINEAR Selection on sorted truckban-shifts (sorted by date)
	 * - Schedule a template for shift
	 * - With DRAW
	 */
    private function runTruckBanScheduleOnBaseHighways()
    {
        $truckBanShifts = $this->runAbstractSchedule($this->truckBanShifts, $this->scheduleResult->getHighwayPairs(), $this->getTargetHighwayShiftNumber(), $this->baseHighwayTemplates, true, true);
        //ReLoad Unused shifts into truckbanShifts
        $this->truckBanShifts = $truckBanShifts['notPairedShifts'];
    }

	/**
	 * Scheduling Templates On unscheduled TruckBan Shifts:
	 * - Select shift From TruckbanShifts
	 * - With Random Selection on truckban-shifts
	 * - Schedule a template for shift
	 * - With FIND
	 */
    private function runTruckBanSchedule()
    {
        //Here we doesn't have to get the remainings shits because the truckbanshit are in availableshifts too.
        //We will just check in anormal schedule, there is no already paired shifts allowed to pair again.
        $truckBanShifts = $this->runAbstractSchedule($this->truckBanShifts, $this->scheduleResult->getHighwayPairs(), $this->getTargetHighwayShiftNumber(), $this->highwayTemplates);
        $this->truckBanShifts = $truckBanShifts['notPairedShifts'];
    }

	/**
	 * Function to remove the preUsed shifts from available shifts.
	 */
	private function removeAlreadyPairedShiftsFromAvailableShifts()
	{
		$pairedShifts = $this->scheduleResult->getHighwayPairShiftIdsArray();
		foreach ($this->availableMobileShifts as $key => $mobileShift) {
			if (in_array($mobileShift->getId(), $pairedShifts)) {
				unset($this->availableMobileShifts[$key]);
			}
		}
	}


	/**
	 * Scheduling  BaseTemplates On Shifts:
	 * - Select shift From Available mobile shifts
	 * - With LINEAR Selection on sorted shifts (sorted by date)
	 * - Schedule a template for shift
	 * - With DRAW
	 */
    private function runBaseHighwaySchedule()
    {
        $notPairedShifts = $this->runAbstractSchedule($this->availableMobileShifts, $this->scheduleResult->getHighwayPairs(), $this->getTargetHighwayShiftNumber(), $this->baseHighwayTemplates, true, true);
	    $pairedItems = array_diff($this->availableMobileShifts, $notPairedShifts['notPairedShifts']);
	    $reUseableItems = array_diff($this->availableMobileShifts, $pairedItems);
	    $this->availableMobileShifts = $reUseableItems;
    }

	/**
	 * Schedule highway shifts in normal way.
	 * - Select shift From Available mobile shifts
	 * - With Random Selection on d shifts (sorted by date)
	 * - Schedule a template for shift
	 * - With FIND
	 */
    private function runHighwaySchedule()
    {
        $notPairedShifts = $this->runAbstractSchedule($this->availableMobileShifts, $this->scheduleResult->getHighwayPairs(), $this->getTargetHighwayShiftNumber(), $this->highwayTemplates);
	    $pairedItems = array_diff($this->availableMobileShifts, $notPairedShifts['notPairedShifts']);
	    $reUseableItems = array_diff($this->availableMobileShifts, $pairedItems);
	    $this->availableMobileShifts = $reUseableItems;

        if ($notPairedShifts['couldNotReachTarget']) {
            //cannot reach hw target
            $this->scheduleResult->getSlaMessages()->set('mobile_hd_shifts_reason', "Nincs elég HD műszak.");
        }
    }

	/**
	 * Scheduling  Base-nonHighway-Templates On Shifts:
	 * - Select shift From Available mobile shifts
	 * - With LINEAR Selection on sorted shifts (sorted by date)
	 * - Schedule a template for shift
	 * - With DRAW
	 */
    private function runBaseNonHighwaySchedule()
    {
        $notPairedShifts = $this->runAbstractSchedule($this->availableMobileShifts, $this->scheduleResult->getNonHighwayPairs(), $this->getTargetMobileShiftNumber() - $this->scheduleResult->getHighwayPairs()->count(), $this->baseNonHighwayTemplates, true, true);
	    $pairedItems = array_diff($this->availableMobileShifts, $notPairedShifts['notPairedShifts']);
	    $reUseableItems = array_diff($this->availableMobileShifts, $pairedItems);
	    $this->availableMobileShifts = $reUseableItems;
    }

	/**
	 * Scheduling in normal way, Nonhighway Templates for the rest of shifts
	 * - Select shift From Available mobile shifts
	 * - Schedule a template for shift
	 * - With FIND
	 */
    private function runNonHighwaySchedule()
    {
        $notPairedShifts = $this->runAbstractSchedule($this->availableMobileShifts, $this->scheduleResult->getNonHighwayPairs(), $this->getTargetMobileShiftNumber() - $this->scheduleResult->getHighwayPairs()->count(), $this->motorwayTemplates);
	    $pairedItems = array_diff($this->availableMobileShifts, $notPairedShifts['notPairedShifts']);
	    $reUseableItems = array_diff($this->availableMobileShifts, $pairedItems);
	    $this->availableMobileShifts = $reUseableItems;
    }

	/**
	 * Schedule Group shifts.
	 * - Random selection
	 * - FIND templates and dont use distance rule
	 */
    private function runGroupShiftSchedule()
    {
        $notPairedShifts = $this->runAbstractSchedule($this->availableGroupShifts, $this->scheduleResult->getGroupPairs(), $this->getTargetGroupShiftNumber(), $this->groupTemplates, false, false, false);
        $this->availableGroupShifts = $notPairedShifts['notPairedShifts'];
    }

	private function runAbstractSchedule(
		array $selectFrom,
		ArrayCollection $resultBag,
		$targetNumber,
		ArrayCollection $drawFrom,
		$isDraw = false,
		$enumSelectionWithFirst = false,
		$useDistanceRuleToFindPair = true
	) {
		$shifts = $selectFrom;
		if (!$enumSelectionWithFirst) {
			shuffle($shifts);
		}
		$notPairedShifts = array();

		while (count($shifts) > 0 &&
			$resultBag->count() != $targetNumber &&
			(!$isDraw || $isDraw && !$drawFrom->isEmpty()))
		{
			$shift = array_shift($shifts);
			$wannabeTemplate = $isDraw ? $this->drawTemplateForShift($shift, $drawFrom, $useDistanceRuleToFindPair) : $this->findTemplateForShift($shift, $drawFrom, $useDistanceRuleToFindPair);
			if ($wannabeTemplate) {
				$resultBag->add(new TemplateShift($shift, $wannabeTemplate, $isDraw));
			} else {
				$notPairedShifts[] = $shift;
			}
		}

		if (!empty($shifts)) {
			$notPairedShifts = array_merge($notPairedShifts, $shifts);
		}
		return array(
			'notPairedShifts' => $notPairedShifts,
			'couldNotReachTarget' => $resultBag->count() != $targetNumber && !$isDraw
		);
	}

	private function findTemplateForShift(Shift $shift, ArrayCollection $templateBag, $useDistanceRuleToFindPair, $startWithStrictSearch = true)
	{
		$possibilities = $templateBag->toArray();
		shuffle($possibilities);

		$found = false;

		while (count($possibilities) > 0 && !$found) {
			$current = array_shift($possibilities);
			$comparator = new TemplateShiftComparator($current, $shift, $startWithStrictSearch, $this->scheduleResult, $this->shiftConfig, $this->templateConfig, $useDistanceRuleToFindPair);
			$found = $comparator->isMatch();
			if (!$found) {
				//remove same possibilities
				$possibilities = array_diff($possibilities, array($current));
			}
		}

		if ($found) {
			return $current;
		} elseif (count($possibilities) <= 0) {
			if ($startWithStrictSearch) { //no result on strictS. try with non-strict
				return $this->findTemplateForShift($shift, $templateBag, $useDistanceRuleToFindPair, false);
			}
			//there is no match even with non-strict search //do nothing
			return;
		}
		//not found and there are no more possibilities
		return;
	}

	private function drawTemplateForShift(Shift $shift, ArrayCollection $templateBag, $useDistanceRuleToFindPair, $startWithStrictSearch = true)
	{
	    //$compareHistory = array();
		$possibilities = $templateBag->toArray();
		shuffle($possibilities);

		$found = false;

		while (count($possibilities) && !$found) {
            $current = array_shift($possibilities);
			$comparator = new TemplateShiftComparator($current, $shift, $startWithStrictSearch, $this->scheduleResult, $this->shiftConfig, $this->templateConfig, $useDistanceRuleToFindPair);
			$found = $comparator->isMatch();
			if (!$found) {
				$possibilities = array_diff($possibilities, array($current));
			}
			//$compareHistory[$current->getId()][] = $shift->getId() . '  reason: ' . $comparator->reason;
		}
		if ($found) {
			$templateBag->removeElement($current);
			return $current;
		} elseif (count($possibilities) <= 0) {
			if ($startWithStrictSearch) { //no result on strictS. try with non-strict
				return $this->drawTemplateForShift($shift, $templateBag, $useDistanceRuleToFindPair, false);
			}
			return;
		}
		return;
	}

    private function runMileageOptimalizer()
    {
	    $optimalizer = new MileageOptimalizer($this);
	    $optimalizer->run();
        $this->optimalizationResult['mileage']['highWay'] = $optimalizer->getHighwayChanges();
        $this->optimalizationResult['mileage']['nonHighWay'] = $optimalizer->getNonHighwayChanges();
        $this->optimalizationResult['mileage']['group'] = $optimalizer->getGroupChanges();
    }

    private function runMeasureRatioOptimalizer()
    {
	    $optimalizer = new MeasureRatioOptimalizer($this);
	    $optimalizer->run();
        $this->optimalizationResult['measureRatio']['highWay'] = $optimalizer->getHighwayChanges();
        $this->optimalizationResult['measureRatio']['nonHighWay'] = $optimalizer->getNonHighwayChanges();
        $this->optimalizationResult['measureRatio']['group'] = $optimalizer->getGroupChanges();
    }

    /****Helper Functions****/
    public function calculateMileageOfFixedMobileShifts()
    {
        $sum = 0;
        foreach ($this->fixedMobileShifts as $shift) {
            $sum += $shift->getTemplate() ? $shift->getTemplate()->getMileage() : 0;
        }
        return $sum;
    }

    public function calculateMileageOfFixedGroupShifts()
    {
        $sum = 0;
        foreach ($this->fixedGroupShifts as $shift) {
            $sum += $shift->getTemplate() ? $shift->getTemplate()->getMileage() : 0;
        }
        return $sum;
    }

    public function calculateFixedMobileMeasureTime()
    {
        $time = 0;
        foreach ($this->fixedMobileShifts as $shift) {
            $time += $shift->getTemplate() ? $shift->getTemplate()->getMeasuredTime() : 0;
        }
        return $time;
    }

    public function calculateFixedMobileDuration()
    {
        $duration = 0;
        foreach ($this->fixedMobileShifts as $shift) {
            $duration += $shift->getTemplate() ? $shift->getTemplate()->getDuration() : 0;
        }
        return $duration;
    }

    public function calculateFixedGroupMeasureTime()
    {
        $time = 0;
        foreach ($this->fixedGroupShifts as $shift) {
            $time += $shift->getTemplate() ? $shift->getTemplate()->getMeasuredTime() : 0;
        }
        return $time;
    }

    public function calculateFixedGroupDuration()
    {
        $duration = 0;
        foreach ($this->fixedGroupShifts as $shift) {
            $duration += $shift->getTemplate() ? $shift->getTemplate()->getDuration() : 0;
        }
        return $duration;
    }

    /* Sorting functions for Optimalizer */

        /* Sorting by mileage */

		    public function sortHighwayTemplatesByMileage()
		    {
		        $templates = $this->highwayTemplates->toArray();
		        usort($templates, array('self', 'templateMileageCompare'));
		        $this->highwayTemplates = new ArrayCollection($templates);
		    }

		    public function sortNonHighwayTemplatesByMileage()
		    {
		        $templates = $this->motorwayTemplates->toArray();
		        usort($templates, array('self', 'templateMileageCompare'));
		        $this->motorwayTemplates = new ArrayCollection($templates);
		    }

		    public function sortGroupTemplatesByMileage()
		    {
		        $templates = $this->groupTemplates->toArray();
		        usort($templates, array('self', 'templateMileageCompare'));
		        $this->groupTemplates = new ArrayCollection($templates);
		    }

		    private static function templateMileageCompare($a, $b)
		    {
		        if ($a->getMileage() == $b->getMileage()) {
		            return 0;
		        }
		        return ($a->getMileage() < $b->getMileage()) ? -1 : 1;
		    }

		/* Sorting by Measure Ratio */

		    public function sortHighwayTemplatesByMeasureRatio()
		    {
		        $templates = $this->highwayTemplates->toArray();
		        usort($templates, array('self', 'templateMeasureRatioCompare'));
		        $this->highwayTemplates = new ArrayCollection($templates);
		    }

		    public function sortNonHighwayTemplatesByMeasureRatio()
		    {
		        $templates = $this->motorwayTemplates->toArray();
		        usort($templates, array('self', 'templateMeasureRatioCompare'));
		        $this->motorwayTemplates = new ArrayCollection($templates);
		    }

		    public function sortGroupTemplatesByMeasureRatio()
		    {
		        $templates = $this->groupTemplates->toArray();
		        usort($templates, array('self', 'templateMeasureRatioCompare'));
		        $this->groupTemplates = new ArrayCollection($templates);
		    }

		    private static function templateMeasureRatioCompare($a, $b)
		    {
		        if ($a->getMeasuredTime() == $b->getMeasuredTime()) {
		            return 0;
		        }
		        return ($a->getMeasuredTime() < $b->getMeasuredTime()) ? 1 : -1;
		    }

	/* Sorting functions for Optimalizer  END */
}