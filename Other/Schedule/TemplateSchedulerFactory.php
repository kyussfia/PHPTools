<?php

namespace PlanBundle\Schedule\Template;

use Doctrine\Common\Persistence\ManagerRegistry;
use PlanBundle\Schedule\Template\Configuration\ShiftConfiguration;
use PlanBundle\Schedule\Template\Configuration\TemplateConfiguration;
use AppBundle\Doctrine\ValueObject\TimePeriod;
use Doctrine\Common\Collections\ArrayCollection;
use PlanBundle\Entity\Zone;
use AppBundle\Doctrine\Query\Filter\Filter;
use AppBundle\Doctrine\Query\Filter\FilterCollection;

use PlanBundle\Schedule\Common\Resource\Timer;

class TemplateSchedulerFactory
{
	/**
	 * @var ManagerRegistry
	 */
	protected $registry;

	/**
	 * @var ShiftConfiguration
	 */
	protected $shiftConfig;

	/**
	 * @var TemplateConfiguration
	 */
	protected $templateConfig;

    /**
     * @var \DateTime
     */
	private $activeToOfTheLatesSchedulableMobileShift;

    /**
     * @var \DateTime
     */
    private $activeToOfTheLatesSchedulableGroupShift;

	/**
	 * Constructor for this class
	 * @param ManagerRegistry       $registry
	 * @param ShiftConfiguration    $shiftConfig
	 * @param TemplateConfiguration $templateConfig
	 */
    public function __construct(ManagerRegistry $registry, ShiftConfiguration $shiftConfig, TemplateConfiguration $templateConfig)
    {
        $this->registry = $registry;
        $this->shiftConfig = $shiftConfig;
        $this->templateConfig = $templateConfig;
    }

	/**
	 * MUST be public! Get registry.
	 *
	 * @return ManagerRegistry
	 */
	public function getRegistry()
	{
		return $this->registry;
	}

	/**
	 * This function creates and returns a Full Month perioded TemplateScheduler object.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return Scheduler\FullMonthTemplateScheduler
	 */
    public function getFullMonthTemplateScheduler(Zone $zone, TimePeriod $period)
    {
        return $this->createTemplateScheduler(Scheduler\FullMonthTemplateScheduler::class, $zone, $period);
    }

	/**
	 * This function creates and returns an Incomplete-Month perioded TemplateScheduler object.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return Scheduler\IncompleteMonthTemplateScheduler
	 */
    public function getIncompleteMonthTemplateScheduler(Zone $zone, TimePeriod $period)
    {
        return $this->createTemplateScheduler(Scheduler\IncompleteMonthTemplateScheduler::class, $zone, $period);
    }

    private function createTemplateScheduler($class, Zone $zone, TimePeriod $period)
    {
        $lastScheduledMobileShift = $this->getLastDateOfScheuledMobileShifts($zone, $period);
        $this->activeToOfTheLatesSchedulableMobileShift = $lastScheduledMobileShift->getTotalCount() > 0 && $lastScheduledMobileShift->getResult()[0]->getActiveTo() >= $period->getEnd() ? $lastScheduledMobileShift->getResult()[0]->getActiveTo() : $period->getEnd();

        $lastScheduledGroupShift = $this->getLastDateOfScheuledGroupShifts($zone, $period);
        $this->activeToOfTheLatesSchedulableGroupShift = $lastScheduledGroupShift->getTotalCount() > 0 && $lastScheduledGroupShift->getResult()[0]->getActiveTo() >= $period->getEnd() ? $lastScheduledGroupShift->getResult()[0]->getActiveTo() : $period->getEnd();

        //$t1 = new Timer();$t2 = new Timer();
	    $truckBanShifts = $this->getTruckBanShifts($zone, $period);

	    $availableMobileShifts = $this->getAvailableMobileShifts($zone, $period)->getResult();
	    $availableGroupShifts = $this->getAvailableGroupShifts($zone, $period)->getResult();

        //dump($t2->time()); //0,289
        //$t2->start();

	    $alreadyPairedNotInZone = $this->createNotInZoneShiftStore($this->getAlreadyPairedMobileShiftsNotInZone($zone, $period)->getResult(), $availableMobileShifts);

        //dump($t2->time()); //10,296
        //$t2->start();

        //calculate timegap for incomplete month
        $firstDayOfStarterMonth = new \DateTime($period->getStart()->format('Y-m-01 00:00:00'));
        $previousDoneTimeGap = new TimePeriod($firstDayOfStarterMonth, $period->getStart());

        //PREvious DONE's
        $prevDoneMobileShifts = $this->getPreviousDoneMobileShifts($zone, $previousDoneTimeGap);
        $prevDoneHighwayMobileShifts = $this->getPreviousDoneHighwayTypedMobileShifts($zone, $previousDoneTimeGap);
        $prevDoneGroupShifts =  $this->getPreviousDoneGroupShifts($zone, $previousDoneTimeGap);

        $fixedMobileShifts = $this->getValidFixedMobileShiftsInMonth($zone, $firstDayOfStarterMonth);
        $fixedHighwayShifts = $this->getValidFixedHighwayShiftsInMonth($zone, $firstDayOfStarterMonth);
        $fixedGroupShifts = $this->getValidFixedGroupShiftsInMonth($zone, $firstDayOfStarterMonth);

        $borderMobileShifts = $this->calculateBorderMobileShifts($zone, $period);
        $borderGroupShifts = $this->calculateBorderGroupShifts($zone, $period);

        //dump($t2->time());//0,51
        //$t2->start();

	    //Min 4 visit on every location
	    $appCollection = $this->getAppearanceCollectionForMinimumAppearancesByZone($zone);
	    $appCollection->optimalizeOnLocationAppearances();

	    if (!$appCollection->isLocationAppearancesOptimalized())
	    {
		    //ha nem lehet optimalizálni a 4 megjelenésre!!!
		    throw new \PlanBundle\Schedule\Template\Exception\MinimumAppearanceException("Templates cannot meet with expectations, to reach minimum appearances");
	    }
        $appCollection->postOptimalizeLocationAppearances($class, $prevDoneMobileShifts, $fixedMobileShifts);
        $templateIds = $appCollection->toTemplateIdArray();

        //dump($t2->time());//0,8
        //$t2->start();

        //BASE BAGS -> All item in it are have to be scheduled
        $baseHighwayTemplates = $this->createBaseHighwayTemplateCollection($templateIds);
        $baseNonHighwayTemplates = $this->createBaseNonHighwayTemplateCollection($templateIds);

        //dump($t2->time());//5,798
        //$t2->start();

        $highwayTemplates = $this->weightTemplatesIntoArray($this->getAvailableHighwayTemplates($zone)->getResult());
        $motorwayTemplates = $this->weightTemplatesIntoArray($this->getAvailableNonHighwayTypedMobileTemplates($zone)->getResult());
        $groupTemplates = $this->weightTemplatesIntoArray($this->getAvailableGroupTemplates($zone)->getResult());

        //dump($t2->time());//8,81
        //$t2->start();
        //dump($t1->time());

        return new $class(
            $zone,
            $baseHighwayTemplates,
            $baseNonHighwayTemplates,
            $truckBanShifts,
            $availableMobileShifts,
            $availableGroupShifts,
            $alreadyPairedNotInZone,
            new ArrayCollection($borderMobileShifts),
            new ArrayCollection($borderGroupShifts),
            new ArrayCollection($prevDoneMobileShifts->getResult()),
            new ArrayCollection($prevDoneHighwayMobileShifts->getResult()),
            new ArrayCollection($prevDoneGroupShifts->getResult()),
            new ArrayCollection($fixedMobileShifts->getResult()),
            new ArrayCollection($fixedHighwayShifts->getResult()),
            new ArrayCollection($fixedGroupShifts->getResult()),
            new ArrayCollection($highwayTemplates),
            new ArrayCollection($motorwayTemplates),
            new ArrayCollection($groupTemplates),
            $period,
            $this->shiftConfig,
            $this->templateConfig
        );
    }

    private function calculateBorderMobileShifts(Zone $zone, TimePeriod $period)
    {
        $rightBorderedMobileShifts = $this->getValidExcludedOverlappingMobileShifts($zone, new TimePeriod($period->getEnd(), $this->activeToOfTheLatesSchedulableMobileShift))->getResult(); //borders from rigth
        return array_merge($rightBorderedMobileShifts, $this->getBorderMobileShiftsInZoneInPeriod($zone, $period)->getResult()); // merge with ones from left
    }

    private function calculateBorderGroupShifts(Zone $zone, TimePeriod $period)
    {
        $rigthBorderedGroupShifts = $this->getValidExcludedOverlappingGroupShifts($zone, new TimePeriod($period->getEnd(), $this->activeToOfTheLatesSchedulableGroupShift))->getResult(); //borders from rigth
        return array_merge($rigthBorderedGroupShifts, $this->getBorderGroupShiftsInZoneInPeriod($zone, $period)->getResult()); //group borders from left
    }

    private function getAppearanceCollectionForMinimumAppearancesByZone($zone)
    {
        //Alap templatek amelyekkel, ha mind kiosztódnak, akkor érintjük az összes elérhető locationt.
        // This function ordered by count(), keep it, cause it will be used in search

        $targetMinimumAppearances = $this->templateConfig->getMinimumAppearancesOnLocations();
        $appearanceCollection = new Model\AppearanceCounterCollection($targetMinimumAppearances, $this->getTemplatesWithAppearancesByZone($zone));

        return $appearanceCollection;
    }

	/**
	 * Create an array with key of the schedulable Shift id, and the value of relevant notInZone shifts during the key-shift's activity period.
	 * @param array $notInZoneShifts
	 * @return array
	 */
    private function createNotInZoneShiftStore(array $notInZoneShifts, array $schedulableShifts)
    {
    	$store = array();
    	foreach ($schedulableShifts as $shift){
		    $firstOverlappingIndex = null;
    		//ráállni az első overlapra
		    for ($i = 0; $i<count($notInZoneShifts); $i++){
			    if ($shift->getActivityPeriod()->overlaps($notInZoneShifts[$i]->getActivityPeriod())){
				    $firstOverlappingIndex = $i;
				    break;
			    }
		    }

		    if (null !== $firstOverlappingIndex) {
	            for ($i = $firstOverlappingIndex; $i<count($notInZoneShifts); $i++){
					if ($shift->getActivityPeriod()->overlaps($notInZoneShifts[$i]->getActivityPeriod())){
						$store[$shift->getId()][] = $notInZoneShifts[$i];
					} else {
						break;
					}
			    }

			    $notInZoneShifts = array_slice($notInZoneShifts, $firstOverlappingIndex);
			    $firstOverlappingIndex = null;
		    } else {
		    	$store[$shift->getId()] = array();
		    }
	    }
    	return $store;
    }

	/**
	 * Get Highway typed templatee to schedule.
	 * @param  array $templateIds
	 * @return
	 */
    private function loadHighwayTemplates($templateIds)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        $filter = new FilterCollection();
        $filter->add(Filter::in('t.id', $templateIds));
        $filter->add(Filter::eq('type.isException', 0));
        return $templateRepo->getHighwayTypeTemplates($filter);
    }

	/**
	 * Get Motorway = non-highway typed templates.
	 * @param  array $templateIds
	 * @return
	 */
    private function loadNonHighwayTemplates($templateIds)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        $filter = new FilterCollection();
        $filter->add(Filter::in('t.id', $templateIds));
        return $templateRepo->getNonHighwayTypedMobileTemplates($filter);
    }

	/**
	 * Create an ArrayCollection within Highway typed basetemplates (required for the minimum appearances).
	 * @param  array            $templateIds
	 * @return ArrayCollection  $highwayTemplates
	 */
    private function createBaseHighwayTemplateCollection($templateIds)
    {
        $templates = $this->loadHighwayTemplates($templateIds);
        $agregatedAppearances = array_count_values($templateIds);

        $highwayTemplates = new ArrayCollection();
        foreach ($templates->getResult() as $template) {
            for ($i = 0; $i < $agregatedAppearances[$template->getId()]; $i++) {
                $highwayTemplates->add($template);
            }
        }

        return $highwayTemplates;
    }

	/**
	 * Create an ArrayCollection within NonHighway typed basetemplates (required for the minimum appearances).
	 * @param  array            $templateIds
	 * @return ArrayCollection  $highwayTemplates
	 */
    private function createBaseNonHighwayTemplateCollection($templateIds)
    {
        $templates = $this->loadNonHighwayTemplates($templateIds);
        $agregatedAppearances = array_count_values($templateIds);

        $nonHighTemplates = new ArrayCollection();
        foreach ($templates->getResult() as $template) {
            for ($i = 0; $i < $agregatedAppearances[$template->getId()]; $i++) {
                $nonHighTemplates->add($template);
            }
        }

        return $nonHighTemplates;
    }

	/**
	 * Get truckban shifts, affected by schedule-period.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return array      $truckBanShifts
	 */
    private function getTruckBanShifts(Zone $zone, TimePeriod $period)
    {
        $truckBanRepo = $this->registry->getManager()->getRepository('PlanBundle:TruckBan');
        $truckBans = $truckBanRepo->findByPeriod($period);

        //$truckBanShifts = new ArrayCollection();
	    $truckBanShifts = array();

        for ($i = 0; $i < count($truckBans); $i++) {
            //decide which part of TB is affected by schedule period
            $dateSliceStart = $truckBans[$i]->getValidFrom();
            $dateSliceEnd = $truckBans[$i]->getValidTo();

            if ($i == 0) { //first TB in the month
                if ($truckBans[$i]->getValidFrom() < $period->getStart()) { //TB starts before our schedule -> chop down TB (head-part)
                    $dateSliceStart = $period->getStart();
                }
            }
            if ($i == count($truckBans)-1) {//last TB in month
                if ($truckBans[$i]->getValidTo() > $period->getEnd()) { //TB ends after our schedule (end date) -> shop down TB (end-part)
                    $dateSliceEnd = $period->getEnd();
                }
            }

            //$truckBanShifts = new ArrayCollection(array_merge($truckBanShifts->toArray(), $this->getAvailableMobileShifts($zone, new TimePeriod($dateSliceStart, $dateSliceEnd))->getResult()));
	        $truckBanShifts = array_merge($truckBanShifts, $this->getAvailableMobileShifts($zone, new TimePeriod($dateSliceStart, $dateSliceEnd))->getResult());
        }

        return $truckBanShifts;
    }

	/**
	 * Get available mobile shifts to schedule.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return
	 */
    private function getAvailableMobileShifts(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        $filter->add(Filter::eq('r.isFixed', 0)); // not fixed shifts
        return $shiftRepository->findShiftsInPeriodByZone($period, $zone, $filter);
    }

    private function getLastDateOfScheuledMobileShifts(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        return $shiftRepository->findLastNonLocalValidShiftsInPeriod($period, $zone, $filter);
    }

    private function getLastDateOfScheuledGroupShifts(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(2))); //group type cars
        return $shiftRepository->findLastNonLocalValidShiftsInPeriod($period, $zone, $filter);
    }

	/**
	 * Get the sorted list of ready-and-valid shifts, not in this Zone, but in this period. Sorted by active_from.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return
	 */
    private function getAlreadyPairedMobileShiftsNotInZone(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1)));
        return $shiftRepository->findNonLocalValidShiftsInPeriodNotInZone($period, $zone, $this->activeToOfTheLatesSchedulableMobileShift, $filter);
    }

    private function getBorderMobileShiftsInZoneInPeriod(Zone $zone, TimePeriod $period)
    {
        $shiftRepo = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1)));
        return $shiftRepo->getBorderShiftsInZoneInPeriod($period, $zone, $filter);
    }

    private function getBorderGroupShiftsInZoneInPeriod(Zone $zone, TimePeriod $period)
    {
        $shiftRepo = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(2)));
        return $shiftRepo->getBorderShiftsInZoneInPeriod($period, $zone, $filter);
    }

	/**
	 * Get Available group shifts to schedule.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return
	 */
    private function getAvailableGroupShifts(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(2))); //group type cars
        $filter->add(Filter::eq('r.isFixed', 0)); // not fixed shifts
        return $shiftRepository->findShiftsInPeriodByZone($period, $zone, $filter);
    }
	/**
	 * Get Fixed mobile-shifts.
	 * @param  Zone      $zone
	 * @param  \DateTime $month
	 * @return
	 */
    private function getValidFixedMobileShiftsInMonth(Zone $zone, \DateTime $month)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        return $shiftRepository->getValidFixedShiftsInMonthByDateAndByZone($month, $zone, $filter);
    }
	/**
	 * Get Fixed group-shifts.
	 * @param  Zone      $zone
	 * @param  \DateTime $month
	 * @return
	 */
    private function getValidFixedGroupShiftsInMonth(Zone $zone, \DateTime $month)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(2))); //group type cars
        return $shiftRepository->getValidFixedShiftsInMonthByDateAndByZone($month, $zone, $filter);
    }

	/**
	 * Get Fixed Highway-shifts.
	 * @param  Zone      $zone
	 * @param  \DateTime $month
	 * @return
	 */
    private function getValidFixedHighwayShiftsInMonth(Zone $zone, \DateTime $month)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        $filter->add(Filter::eq('template.type.isException', 0));
        $filter->add(Filter::eq('rt.id', 1));
        return $shiftRepository->getValidFixedShiftsInMonthByDateAndByZone($month, $zone, $filter);
    }

	/**
	 * Get previously done mobile-shifts in period.
	 * @param  Zone                  $zone
	 * @param  TimePeriod            $period
	 * @param  FilterCollection|null $filter
	 * @return
	 */
    private function getPreviousDoneMobileShifts(Zone $zone, TimePeriod $period, FilterCollection $filter = null)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        if (!$filter) {
            $filter = new FilterCollection();
        }
        $filter->add(Filter::isNull('r.problem'));
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        $filter->add(Filter::eq('r.isFixed', 0)); //non fixed
        return $shiftRepository->findNonLocalValidShiftsInPeriodByZone($period, $zone, $filter);
    }

    private function getValidExcludedOverlappingMobileShifts(Zone $zone, TimePeriod $period, FilterCollection $filter = null)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        if (!$filter) {
            $filter = new FilterCollection();
        }
        $filter->add(Filter::isNull('r.problem'));
        $filter->add(Filter::in('ct.id', array(1))); //mobil type cars
        return $shiftRepository->findNonLocalValidShiftsInPeriodByZone($period, $zone, $filter);
    }

    private function getValidExcludedOverlappingGroupShifts(Zone $zone, TimePeriod $period, FilterCollection $filter = null)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        if (!$filter) {
            $filter = new FilterCollection();
        }
        $filter->add(Filter::isNull('r.problem'));
        $filter->add(Filter::in('ct.id', array(2))); //group type cars
        return $shiftRepository->findNonLocalValidShiftsInPeriodByZone($period, $zone, $filter);
    }

	/**
	 * Get previously done highway-shifts in period.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return
	 */
    private function getPreviousDoneHighwayTypedMobileShifts(Zone $zone, TimePeriod $period)
    {
        $filter = new FilterCollection();
        $filter->add(Filter::eq('template.type.isException', 0));
        $filter->add(Filter::eq('template.type.roadType', 1));
        $filter->add(Filter::eq('r.isFixed', 0));
        return $this->getPreviousDoneMobileShifts($zone, $period, $filter);
    }

	/**
	 * Get previously done group-shifts.
	 * @param  Zone       $zone
	 * @param  TimePeriod $period
	 * @return
	 */
    private function getPreviousDoneGroupShifts(Zone $zone, TimePeriod $period)
    {
        $shiftRepository = $this->registry->getManager()->getRepository('PlanBundle:Shift');
        $filter = new FilterCollection();
        $filter->add(Filter::isNull('r.problem'));
        $filter->add(Filter::in('ct.id', array(2))); //group type cars
        $filter->add(Filter::eq('r.isFixed', 0)); //non fixed
        return $shiftRepository->findNonLocalValidShiftsInPeriodByZone($period, $zone, $filter);
    }

	/**
	 * BaseFilter function to get available templates to schedule.
	 * Helper function.
	 *
	 * @param  Zone             $zone
	 * @return FilterCollection $filter
	 */
    private function prepareFilterToGetAvailableTemplates(Zone $zone)
    {
        $filter = new FilterCollection();
        $filter->add(Filter::eq('zone.id', $zone->getId()));    //adott zónába
        $filter->add(Filter::eq('is_active', true));   //csak aktív templatek
        $filter->add(Filter::eq('is_projected', true));    //tervezésre bocsájtott templatek
        $filter->add(Filter::eq('type.is_projected', true)); //tervezésre bocsájtott template típusok
        return $filter;
    }

	/**
	 * Get available highway-typed templates.
	 * @param  Zone   $zone
	 * @return
	 */
    private function getAvailableHighwayTemplates(Zone $zone)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        $filter = $this->prepareFilterToGetAvailableTemplates($zone);
        $filter->add(Filter::eq('type.isException', 0));
        return $templateRepo->getHighwayTypeTemplates($filter);
    }

	/**
	 * Get available motorway templates.
	 * @param  Zone   $zone
	 * @return
	 */
    private function getAvailableNonHighwayTypedMobileTemplates(Zone $zone)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        $filter = $this->prepareFilterToGetAvailableTemplates($zone);

        return $templateRepo->getNonHighwayTypedMobileTemplates($filter);
    }

	/**
	 * Get available group templates.
	 * @param  Zone   $zone
	 * @return
	 */
    private function getAvailableGroupTemplates(Zone $zone)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        return $templateRepo->getGroupTypeTemplates($this->prepareFilterToGetAvailableTemplates($zone));
    }

    /**
     * Weighting a queryobject contains templates.
     *
     * @param $templateArray
     * @return array
     */
    private function weightTemplatesIntoArray($templateArray)
    {
        $weightedArray = array();
        foreach ($templateArray as $template) {
            for ($i = 0;$i < $template->getWeight(); $i++) {
                $weightedArray[] = $template;
            }
        }
        return $weightedArray;
    }

	/**
	 * Get templates and other infromations, to calculate BaseTemplates.
	 * @param  Zone   $zone
	 * @return
	 */
    private function getTemplatesWithAppearancesByZone(Zone $zone)
    {
        $templateRepo = $this->registry->getManager()->getRepository('PlanBundle:Template');
        return $templateRepo->getMobileTemplatesWithAppearancesByZone($zone);
    }
}