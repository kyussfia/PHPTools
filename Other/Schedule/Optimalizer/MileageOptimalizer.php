<?php

namespace PlanBundle\Schedule\Template\Optimalizer;

use PlanBundle\Schedule\Template\AbstractTemplateScheduler;
use Doctrine\Common\Collections\ArrayCollection;

class MileageOptimalizer extends Optimalizer
{
    public function __construct(AbstractTemplateScheduler $scheduler)
    {
        parent::__construct($scheduler);
        $this->originalQuota = $scheduler->calculateMileageQuota();
        $this->isOptimalized = $scheduler->calculateMileageQuota() <= $scheduler->getZone()->getMobileMileageQuota();
        $this->originalGroupQuota = $scheduler->calculateGroupMileageQuota();
        $this->isGroupOptimalized = $scheduler->calculateGroupMileageQuota() <= $scheduler->getZone()->getGroupMileageQuota();
    }

    public function run()
    {
        //initial sorting data of options
        $this->initSortingOptions();

        if (!$this->scheduler->getScheduleResult()->getMobilePairs()->isEmpty()) {
            if ($this->scheduler->getZone()->getMobileMileageQuota()) {
                $this->runMobileOptimalization();
                if ($this->isOptimalized || $this->isNewQuotaBetterThanOld()) {
                    $this->scheduler->setScheduleResult($this->optimalizedData);
                }
                if (!$this->isOptimalized && $this->noFluctuation) {
                    $this->scheduler->getScheduleResult()->getSlaMessages()->set('mobile_mileage_quota_reason', "A mobil adatgyűjtés km kvótája nem optimalizálható cserélgetéssel sem.");
                }
            } else {
                $this->scheduler->getScheduleResult()->getSlaMessages()->set('mobile_mileage_quota_reason', "A mobil adatgyűjtés km kvótája nincs megadva így nem optimalizálható.");
            }
        }
        if (!$this->scheduler->getScheduleResult()->getGroupPairs()->isEmpty()) {
            if ($this->scheduler->getZone()->getGroupMileageQuota()) {
                $this->runGroupOptimalization();
                if (!$this->isGroupOptimalized && $this->noFluctuation) {
                    $this->scheduler->getScheduleResult()->getSlaMessages()->set('group_mileage_quota_reason', "A csoportos ellenőrzés km kvótája nem optimalizálható cserélgetéssel sem.");
                }
            } else {
                $this->scheduler->getScheduleResult()->getSlaMessages()->set('group_mileage_quota_reason', "A csoportos ellenőrzés km kvótája nincs megadva így nem optimalizálható.");
            }
        }
        return $this;
    }

    public function initSortingOptions()
    {
        $this->scheduler->sortHighwayTemplatesByMileage();
        $this->scheduler->sortNonHighwayTemplatesByMileage();
        $this->scheduler->sortGroupTemplatesByMileage();
    }

    public function sortingResult()
    {
        $this->optimalizedData->setHighwayPairs(new ArrayCollection($this->optimalizedData->getSortedHighwayPairsByMileage()));
        $this->optimalizedData->setNonHighwayPairs(new ArrayCollection($this->optimalizedData->getSortedNonHighwayPairsByMileage()));
    }

    public function sortingGroupResult()
    {
        $this->optimalizedData->setGroupPairs(new ArrayCollection($this->optimalizedData->getSortedGroupPairsByMileage()));
    }

    public function isHighwayMustBeOptimalized($highwayPair, $nonHighwayPair)
    {
        return $highwayPair->getTemplate()->getMileage() > $nonHighwayPair->getTemplate()->getMileage();
    }

    public function isNonHighwayMustBeOptimalized($highwayPair, $nonHighwayPair)
    {
        return $highwayPair->getTemplate()->getMileage() <= $nonHighwayPair->getTemplate()->getMileage();
    }

    public function isNewTemplateBetterOptimalized($template, $oldTemplate)
    {
        return $template->getMileage() < $oldTemplate->getMileage();
    }

    public function isNewQuotaOptimalized()
    {
        return $this->scheduler->calculateMileageQuotaWithResult($this->optimalizedData) <= $this->scheduler->getZone()->getMobileMileageQuota();
    }

    public function isNewQuotaBetterThanOld()
    {
        return $this->originalQuota > $this->scheduler->calculateMileageQuotaWithResult($this->optimalizedData);
    }

    public function isNewGroupQuotaOptimalized()
    {
        return $this->scheduler->calculateGroupMileageQuotaWithResult($this->optimalizedData) <= $this->scheduler->getZone()->getGroupMileageQuota();
    }

    public function isNewGroupQuotaBetterThanOld()
    {
        return $this->originalGroupQuota > $this->scheduler->calculateGroupMileageQuotaWithResult($this->optimalizedData);
    }
}