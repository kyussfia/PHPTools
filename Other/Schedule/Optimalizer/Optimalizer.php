<?php

namespace PlanBundle\Schedule\Template\Optimalizer;

use Doctrine\Common\Collections\ArrayCollection;
use PlanBundle\Schedule\Template\AbstractTemplateScheduler;
use PlanBundle\Schedule\Template\Model\TemplateShift;
use PlanBundle\Schedule\Template\Comparator\TemplateShiftComparator;
use PlanBundle\Entity\Shift;

abstract class Optimalizer
{
    protected $highwayChanges;

    protected $nonHighwayChanges;

    protected $groupChanges;

    protected $scheduler;

    protected $originalData;

    protected $originalQuota;

    protected $originalGroupQuota;

    protected $optimalizedData; // to modify

    protected $isOptimalized;

    protected $isGroupOptimalized;

    protected $noFluctuation;

    protected $visitedElements;

    protected $switchedRequireds;

    public function __construct(AbstractTemplateScheduler $scheduler)
    {
        $this->highwayChanges = 0;
        $this->nonHighwayChanges = 0;
        $this->groupChanges = 0;
        $this->scheduler = $scheduler;
        $this->originalData = $scheduler->getScheduleResult();
        $this->optimalizedData = clone $scheduler->getScheduleResult(); 
        $this->noFluctuation = false;

        $this->visitedElements = new ArrayCollection();
        $this->switchedRequireds = new ArrayCollection();
    }

    public function getHighwayChanges()
    {
        return $this->highwayChanges;
    }

    public function getNonHighwayChanges()
    {
        return $this->nonHighwayChanges;
    }

    public function getGroupChanges()
    {
        return $this->groupChanges;
    }

    abstract public function run();

	private function findTemplateForShift(Shift $shift, ArrayCollection $templateBag, $useDistance = true, $startWithStrictSearch = true)
	{
		//THERE IS NO  SHUFFLE NEEDED
		$possibilities = $templateBag->toArray();
		$found = false;

		while (count($possibilities) > 0 && !$found) {
			$current = array_shift($possibilities);
			$comparator = new TemplateShiftComparator($current, $shift, $startWithStrictSearch, $this->optimalizedData, $this->scheduler->getShiftConfig(), $this->scheduler->getTemplateConfig(), $useDistance);
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
				return $this->findTemplateForShift($shift, $templateBag, $useDistance, false);
			}
			return;
		}
		return;
	}

    public function runGroupOptimalization()
    {
        $this->noFluctuation = false;

        for ($i=0; !$this->isGroupOptimalized && !$this->noFluctuation;) {
            $this->sortingGroupResult();

            if ($i<$this->optimalizedData->getGroupPairs()->count() && in_array($this->optimalizedData->getGroupPairs()->get($i)->getShift(), $this->visitedElements->toArray())) {
                $i++;
            }

            if ($this->optimalizedData->getGroupPairs()->get($i)) {
	            $resultTemplate = $this->findTemplateForShift($this->optimalizedData->getGroupPairs()->get($i)->getShift(), $this->scheduler->getGroupTemplates(), false);

                if ($resultTemplate && $this->isNewTemplateBetterOptimalized($resultTemplate, $this->optimalizedData->getGroupPairs()->get($i)->getTemplate())) {
                    //find elem to modify
                    $newPair = new TemplateShift($this->optimalizedData->getGroupPairs()->get($i)->getShift(), $resultTemplate);
                    //remove oldPair
                    $this->optimalizedData->getGroupPairs()->removeElement($this->optimalizedData->getGroupPairs()->get($i));
                    $this->optimalizedData->getGroupPairs()->add($newPair);
                    $this->groupChanges++;
                } else {
                    $this->visitedElements->add($this->optimalizedData->getGroupPairs()->get($i)->getShift());
                }
            }

            $this->noFluctuation = $i == $this->optimalizedData->getGroupPairs()->count();
            $this->isGroupOptimalized = $this->isNewGroupQuotaOptimalized();
        }

        if ($this->isGroupOptimalized || $this->isNewGroupQuotaBetterThanOld()) {
            $this->scheduler->setScheduleResult($this->optimalizedData);
        }
    }

    public function runMobileOptimalization($avoidRequiredPairs = true)
    {
        for ($i = 0, $j = 0; !$this->isOptimalized && !$this->noFluctuation;) {
            $this->sortingResult();

            if ($i<$this->optimalizedData->getHighwayPairs()->count() && in_array($this->optimalizedData->getHighwayPairs()->get($i)->getShift(), $this->visitedElements->toArray())) {
                $i++;
            }

            if ($j<$this->optimalizedData->getNonHighwayPairs()->count() && in_array($this->optimalizedData->getNonHighwayPairs()->get($j)->getShift(), $this->visitedElements->toArray())) {
                $j++;
            }

            if ($this->optimalizedData->getHighwayPairs()->get($i) && ($j==$this->optimalizedData->getNonHighwayPairs()->count() || $this->isHighwayMustBeOptimalized($this->optimalizedData->getHighwayPairs()->get($i), $this->optimalizedData->getNonHighwayPairs()->get($j)))) {
                if ($avoidRequiredPairs && $this->optimalizedData->getHighwayPairs()->get($i)->isRequired()) {
                    $this->visitedElements->add($this->optimalizedData->getHighwayPairs()->get($i)->getShift());
                } else {
	                $resultTemplate = $this->findTemplateForShift($this->optimalizedData->getHighwayPairs()->get($i)->getShift(), $this->scheduler->getHighwayTemplates());

                    if ($resultTemplate && $this->isNewTemplateBetterOptimalized($resultTemplate, $this->optimalizedData->getHighwayPairs()->get($i)->getTemplate())) {
                        if ($this->optimalizedData->getHighwayPairs()->get($i)->isRequired()) {
                            $this->switchedRequireds->set($resultTemplate->getId(), $this->optimalizedData->getHighwayPairs()->get($i)->getTemplate()->getId());
                        }
                        //find elem to modify
                        $log = "Switch Shift #".$this->optimalizedData->getHighwayPairs()->get($i)->getShift()->getId()." switch Template #".$this->optimalizedData->getHighwayPairs()->get($i)->getTemplate()->getId()." -> to #".$resultTemplate->getId();
                        //dump($log);
                        $newPair = new TemplateShift($this->optimalizedData->getHighwayPairs()->get($i)->getShift(), $resultTemplate);
                        //remove oldPair
                        $this->optimalizedData->getHighwayPairs()->removeElement($this->optimalizedData->getHighwayPairs()->get($i));
                        $this->optimalizedData->getHighwayPairs()->add($newPair);
                        $this->highwayChanges++;
                    } else {
                        $this->visitedElements->add($this->optimalizedData->getHighwayPairs()->get($i)->getShift());
                    }
                } 
            } elseif ($this->optimalizedData->getNonHighwayPairs()->get($j) && ($i==$this->optimalizedData->getHighwayPairs()->count() || $this->isNonHighwayMustBeOptimalized($this->optimalizedData->getHighwayPairs()->get($i), $this->optimalizedData->getNonHighwayPairs()->get($j)))) {
                if ($avoidRequiredPairs && $this->optimalizedData->getNonHighwayPairs()->get($j)->isRequired()) {
                    $this->visitedElements->add($this->optimalizedData->getNonHighwayPairs()->get($j)->getShift());
                } else {
	                $resultTemplate = $this->findTemplateForShift($this->optimalizedData->getNonHighwayPairs()->get($j)->getShift(), $this->scheduler->getNonHighwayTemplates());

                    if ($resultTemplate && $this->isNewTemplateBetterOptimalized($resultTemplate, $this->optimalizedData->getNonHighwayPairs()->get($j)->getTemplate())) {
                        if ($this->optimalizedData->getNonHighwayPairs()->get($j)->isRequired()) {
                            $this->switchedRequireds->set($resultTemplate->getId(), $this->optimalizedData->getNonHighwayPairs()->get($j)->getTemplate()->getId());
                        }
                        $log = "Switch Shift #".$this->optimalizedData->getNonHighwayPairs()->get($j)->getShift()->getId()." switch Template #".$this->optimalizedData->getNonHighwayPairs()->get($j)->getTemplate()->getId()." -> to #".$resultTemplate->getId();
                        //dump($log);
                        //find elem to modify
                        $newPair = new TemplateShift($this->optimalizedData->getNonHighwayPairs()->get($j)->getShift(), $resultTemplate);
                        //remove oldPair
                        $this->optimalizedData->getNonHighwayPairs()->removeElement($this->optimalizedData->getNonHighwayPairs()->get($j));
                        $this->optimalizedData->getNonHighwayPairs()->add($newPair);
                        $this->nonHighwayChanges++;
                    } else {
                        $this->visitedElements->add($this->optimalizedData->getNonHighwayPairs()->get($j)->getShift());
                    }
                }
            }
            $this->noFluctuation = $i == $this->optimalizedData->getHighwayPairs()->count() && $j == $this->optimalizedData->getNonHighwayPairs()->count();
            $this->isOptimalized = $this->isNewQuotaOptimalized();
        }
    }

    abstract public function initSortingOptions();

    abstract public function sortingResult();
    abstract public function sortingGroupResult();

    abstract public function isHighwayMustBeOptimalized($highwayPair, $nonHighwayPair);
    abstract public function isNonHighwayMustBeOptimalized($highwayPair, $nonHighwayPair);

    abstract public function isNewTemplateBetterOptimalized($template, $oldTemplate);
    abstract public function isNewGroupQuotaBetterThanOld();

    abstract public function isNewQuotaOptimalized();
    abstract public function isNewGroupQuotaOptimalized();
}