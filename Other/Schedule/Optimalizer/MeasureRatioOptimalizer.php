<?php

namespace PlanBundle\Schedule\Template\Optimalizer;

use PlanBundle\Schedule\Template\AbstractTemplateScheduler;
use Doctrine\Common\Collections\ArrayCollection;

class MeasureRatioOptimalizer extends Optimalizer
{
    public function __construct(AbstractTemplateScheduler $scheduler)
    {
        parent::__construct($scheduler);
        $this->originalQuota = $scheduler->calculateMeasureRatioQuota();
        $this->isOptimalized = $scheduler->calculateMeasureRatioQuota() >= 58;
    }

    public function run()
    {
        //initial sorting data of options
        $this->initSortingOptions();

        if (!$this->scheduler->getScheduleResult()->getMobilePairs()->isEmpty()) {
            $this->runMobileOptimalization();
            if ($this->isOptimalized || $this->isNewQuotaBetterThanOld()) {
                $this->scheduler->setScheduleResult($this->optimalizedData);
            } else {
                $this->highwayChanges = 0;
                $this->nonHighwayChanges = 0;
                $this->optimalizedData = clone $this->scheduler->getScheduleResult();
                $this->runMobileOptimalization(false);
                // Itt romlik el a minimum 4 megjel
                $this->scheduler->getScheduleResult()->getSlaMessages()->get('minimum_location_appearances_reason', "Nem hozható az adatgyűjtési időhányad a minimum megjelenések megsértése nélkül.");
                if ($this->isOptimalized || $this->isNewQuotaBetterThanOld()) {
                    $this->scheduler->setScheduleResult($this->optimalizedData);
                    $this->scheduler->setMisMeasureOptimalizedTemplates($this->switchedRequireds);
                }
            }
            if (!$this->isOptimalized && $this->noFluctuation) {
                $this->scheduler->getScheduleResult()->getSlaMessages()->set('mobile_measure_ratio_reason', "A mobil adatgyűjtési időhányad nem optimalizálható cserélgetéssel sem.");
            }
        }
        if (!$this->scheduler->getScheduleResult()->getGroupPairs()->isEmpty()) {
            $this->runGroupOptimalization();
            if (!$this->isGroupOptimalized && $this->noFluctuation) {
                $this->scheduler->getScheduleResult()->getSlaMessages()->set('group_measure_ratio_reason', "A csoportos ellenőrzési időhányad nem optimalizálható cserélgetéssel sem.");
            }
        }
        return $this;
    }

    public function initSortingOptions()
    {
        $this->scheduler->sortHighwayTemplatesByMeasureRatio();
        $this->scheduler->sortNonHighwayTemplatesByMeasureRatio();
        $this->scheduler->sortGroupTemplatesByMeasureRatio();
    }

    public function sortingResult()
    {
        $this->optimalizedData->setHighwayPairs(new ArrayCollection($this->optimalizedData->getSortedHighwayPairsByMeasureRatio()));
        $this->optimalizedData->setNonHighwayPairs(new ArrayCollection($this->optimalizedData->getSortedNonHighwayPairsByMeasureRatio()));
    }

    public function sortingGroupResult()
    {
        $this->optimalizedData->setGroupPairs(new ArrayCollection($this->optimalizedData->getSortedGroupPairsByMeasureRatio()));
    }

    public function isHighwayMustBeOptimalized($highwayPair, $nonHighwayPair)
    {
        return $highwayPair->getTemplate()->getMeasuredTime() < $nonHighwayPair->getTemplate()->getMeasuredTime();
    }

    public function isNonHighwayMustBeOptimalized($highwayPair, $nonHighwayPair)
    {
        return $highwayPair->getTemplate()->getMeasuredTime() >= $nonHighwayPair->getTemplate()->getMeasuredTime();
    }

    public function isNewTemplateBetterOptimalized($template, $oldTemplate)
    {
        return $template->getMeasuredTime() > $oldTemplate->getMeasuredTime();
    }

    public function isNewQuotaOptimalized()
    {
        return $this->scheduler->calculateMeasureRatioQuotaWithResult($this->optimalizedData) >= 58;
    }

    public function isNewQuotaBetterThanOld()
    {
        return $this->originalQuota < $this->scheduler->calculateMeasureRatioQuotaWithResult($this->optimalizedData);
    }

    public function isNewGroupQuotaOptimalized()
    {
        return $this->scheduler->calculateGroupMeasureRatioQuotaWithResult($this->optimalizedData) >= 58;
    }

    public function isNewGroupQuotaBetterThanOld()
    {
        return $this->originalGroupQuota < $this->scheduler->calculateMeasureRatioQuotaWithResult($this->optimalizedData);
    }
}