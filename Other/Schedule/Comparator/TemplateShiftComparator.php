<?php

namespace PlanBundle\Schedule\Template\Comparator;

use PlanBundle\Entity\Shift;
use PlanBundle\Entity\Template;
use PlanBundle\Schedule\Template\Configuration\ShiftConfiguration;
use PlanBundle\Schedule\Template\Configuration\TemplateConfiguration;
use PlanBundle\Schedule\Template\Model\ScheduleResult;


class TemplateShiftComparator extends Comparator
{
    protected $comparedOnes;

    protected $shiftConfig;

    protected $templateConfig;

	protected $useDistanceRule;

	//public $reason;

    public function __construct(Template $template, Shift $shift, $strict, ScheduleResult $comparedOnes, ShiftConfiguration $shiftConfig, TemplateConfiguration $templateConfig, $useDistanceRule = true)
    {   
        $this->comparedOnes = $comparedOnes;
        $this->shift = $shift;
        $this->template = $template;
        $this->strictMatch = $strict;
        $this->shiftConfig = $shiftConfig;
        $this->templateConfig = $templateConfig;
	    $this->useDistanceRule = $useDistanceRule;
	    //$this->reason = '';
    }

    public function strictRule()
    {
        $acceptance = $this->shiftConfig->acceptTemplate($this->shift, $this->template);
        //$this->reason .= " ;strict: ". $acceptance;
        return $acceptance;
    }

    public function baseRule()
    {
        $availability = $this->isTemplateFree($this->shift, $this->template);
        $compliance = $this->templateCompliance();
        $isFarEnough = $this->useDistanceRule ? $this->distanceCompliance() : true;
        //$this->reason .= " ;avail: " . $availability . " ;comp: " . $compliance . " ;isFar: ". $isFarEnough;
        return $compliance && $availability && $isFarEnough;
    }

    private function templateCompliance()
    {
        $compliance = $this->template->complyWith($this->shift);
        //$this->reason .= " templ: ". json_encode($compliance);
        return is_bool($compliance) && $compliance;
    }

	/**
	 * Returns false if in the $this->shift's time, there are no road under a location of template.
	 * Returns false too if there any valid reserved location (aka shift) in 50km, so this pair is not compatible with each other.
	 * Returns true if there are not.
	 *
	 * @return bool
	 */
    private function distanceCompliance()
    {
        $templateLocationsArray = $this->template->getActivities()->getMeasureLocations();

        $relevantInZoneBorderShifts = $this->comparedOnes->getMobileBorderShiftsInPeriod($this->shift->getActivityPeriod()); //az időszakunkban végződő műszakok
        $relevantNotInZoneShifts = $this->comparedOnes->getNotInZoneShiftsByShiftId($this->shift->getId());
        $pairedShifts = $this->comparedOnes->getMobilePairsByPeriod($this->shift->getActivityPeriod());
        $fixedShifts = $this->comparedOnes->getFixedShiftsInZone($this->shift->getActivityPeriod());

        foreach ($templateLocationsArray as $location) {
            //get inTime roadlocation
            $roadLocation = $location->getRoadLocationInDate($this->shift->getActiveFrom());
            if (!$roadLocation || !$roadLocation->getRoadOf()) {
                //throw new \PlanBundle\Schedule\Template\Exception\InvalidTemplateException("Invalid Template during scheduling: Location #".$location->getId()." has no road under itself at ".$this->shift->getActiveFrom()->format("Y-m-d H:i:s")."! (TemplateId #".$this->template->getId().")");
	            return false;
            }

            $oneRoute = $roadLocation->getRoadOf()->getRouteNumber();
            $oneDirection = $location->getDirection();
            $oneMilestone = $roadLocation->getMilestone();
            /////////////////////////////////////////////////////
            ///Done shifts not in Zone
            $isValidByOutterZone = $this->isGivenValuesInInvalidDistanceWithShifts($relevantNotInZoneShifts, $oneRoute, $oneDirection, $oneMilestone);
            ///Paired Shifts
            $isValidByCurrentSchedule = $this->isGivenValuesInInvalidDistanceWithShifts($pairedShifts, $oneRoute, $oneDirection, $oneMilestone);
            ///Fixed Shifts
            $isValidByFixeds = $this->isGivenValuesInInvalidDistanceWithShifts($fixedShifts, $oneRoute, $oneDirection, $oneMilestone);
            $isValidByBorders = $this->isGivenValuesInInvalidDistanceWithShifts($relevantInZoneBorderShifts, $oneRoute, $oneDirection, $oneMilestone);

            if (!$isValidByOutterZone || !$isValidByCurrentSchedule || !$isValidByFixeds || !$isValidByBorders) {
                return false;
            }
        }
        return true;
    }

	/**
	 * Returns false if there are any reserved (already paired ~ valid) shift is in the $givenroute route, in $givenDirection direction in 50 km distance.
	 * Return true if there are not.
	 *
	 * @param $shifts
	 * @param $givenRoute
	 * @param $givenDirection
	 * @param $givenMilestone
	 * @return bool
	 * @throws \PlanBundle\Schedule\Template\Exception\InvalidTemplateException
	 */
    private function isGivenValuesInInvalidDistanceWithShifts($shifts, $givenRoute, $givenDirection, $givenMilestone)
    {
        foreach ($shifts as $shift) {
            $reservedLocations = $shift->getTemplate()->getActivities()->getMeasureLocations();
            foreach ($reservedLocations as $reservedLocation) {
                $reservedRoadLocation = $reservedLocation->getRoadLocationInDate($this->shift->getActiveFrom());
                if (!$reservedRoadLocation || !$reservedRoadLocation->getRoadOf()) {
                    $e = new \PlanBundle\Schedule\Template\Exception\InvalidTemplateException();
                    $e->setData(array(
                        'locationName' => $reservedLocation->getName(),
                        'shiftActivityPeriod' => $this->shift->getActiveFrom()->format("Y-m-d H:i:s"),
                        'zoneName' => $shift->getZone()->getName(),
                        'templateId' => $shift->getTemplate()->getId()
                    ));
                    throw $e;
                }
                $reservedRoute = $reservedRoadLocation->getRoadOf()->getRouteNumber();
                $reservedDirection = $reservedLocation->getDirection();
                $reservedMilestone = $reservedRoadLocation->getMilestone();

                if ($reservedRoute == $givenRoute && $reservedDirection == $givenDirection && abs($reservedMilestone - $givenMilestone) < $this->templateConfig->getMinimumDistanceBetweenMeasures()) {
                    return false;
                }
            }
        }
        return true;
    }

    private function isTemplateFree(Shift $shift, Template $template)
    {
        $freeViaPlanneds = $this->isTemplateFreeDuringShifts($this->comparedOnes->mergePairs(), true, $shift, $template);
        $freeViaFixeds = $this->isTemplateFreeDuringShifts($this->comparedOnes->getFixedShiftsInZone($shift->getActivityPeriod()), false, $shift, $template);
        $freeViaBorders = $this->isTemplateFreeDuringShifts($this->comparedOnes->getBorderShiftsArray(), false, $shift, $template);
        return $freeViaFixeds && $freeViaPlanneds && $freeViaBorders;
    }

    private function isTemplateFreeDuringShifts($shifts, $areShiftsPairs, Shift $shift, Template $template)
    {
        foreach ($shifts as $pair) {
            if (($areShiftsPairs && ($pair->getShift()->getId() != $shift->getId())) || (!$areShiftsPairs && ($pair->getId() != $shift->getId()))) {
                if (($pair->getActivityPeriod()->overlaps($shift->getActivityPeriod())  //found a shift overlapping the new one
                        || $pair->getActivityPeriod()->contains($shift->getActivityPeriod()) //found a shift contains the new one
                        || $shift->getActivityPeriod()->contains($pair->getActivityPeriod()))  // the new shift contains an elder one
                    && ($pair->getTemplate() == $template || $this->templatesHasSameMeasureLocations($pair->getTemplate(), $template))) {
                    return false;
                }
            }
        }
        return true;
    }

    private function templatesHasSameMeasureLocations($template1, $template2)
    {
        $reservedLocations = $template1->getActivities()->getMeasureLocations();
        foreach ($reservedLocations as $reservedLocation)
        {
            if (in_array($reservedLocation, $template2->getActivities()->getMeasureLocations()))
            {
                return true;
            }
        }
        return false;
    }
}