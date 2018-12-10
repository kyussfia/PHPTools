<?php

namespace PlanBundle\Schedule\Template\Model;

use PlanBundle\Schedule\Template\Scheduler\IncompleteMonthTemplateScheduler;
use PlanBundle\Schedule\Template\Model\LocationAppearance;
use PlanBundle\Schedule\Template\Model\LocationAppearanceCollection;
use AppBundle\Doctrine\ValueObject\TimePeriod;

class AppearanceCounterCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    private $minimumAppearanceNumber;

    public function __construct(int $minimumAppearanceNumber, array $templateAppearances = array())
    {
        $this->minimumAppearanceNumber = $minimumAppearanceNumber;

        $elements = array();
        foreach ($templateAppearances as $templateAppearance) {
            $newAppearance = new TemplateAppearance($templateAppearance['t_id'], $templateAppearance['l_id'], $templateAppearance['appearances']);
            $elements[] = new AppearanceCounter($newAppearance);
        }
        parent::__construct($elements);
    }

    //return LocationAppearanceCollection
    public function calculateAppearancesByLocation()
    {
        $locationApps = new LocationAppearanceCollection();
        foreach ($this as $appearanceCounter) {
            $findableElem = new LocationAppearance($appearanceCounter->getTemplateAppearance()->getLocationId(), $appearanceCounter->getInstances() * $appearanceCounter->getTemplateAppearance()->getAppearances());
            $elemIndex = $locationApps->indexOfLocationId($appearanceCounter->getTemplateAppearance()->getLocationId());
            if ($elemIndex !== FALSE) { //location already added -> increment appearance
                $elemToModify = $locationApps->get($elemIndex);
                $elemToModify->setAppearances($elemToModify->getAppearances() + $findableElem->getAppearances());
                $locationApps->set($elemIndex, $elemToModify);
            } else {
                $locationApps->add($findableElem);
            }
        }
        return $locationApps;
    }

    public function optimalizeOnLocationAppearances()
    {
        $currentLocationApps = $this->calculateAppearancesByLocation();
        foreach ($currentLocationApps as $key => $currentLocationAppearance) {
            $locationId = $currentLocationAppearance->getLocationId();
            $appearsOnLocation = $currentLocationAppearance->getAppearances();
            while ($appearsOnLocation < $this->minimumAppearanceNumber) {
                $increasedBy = $this->searchAppearanceCounterToIncrementAppearanceOn($locationId);
                $appearsOnLocation += $increasedBy;
            }
        }

        return $this;
    }

    public function postOptimalizeLocationAppearances($fullOrIncompleteMonth, $prevDoneMobileShifts, $fixedMobileShifts)
    {
        if ($fullOrIncompleteMonth == IncompleteMonthTemplateScheduler::class) {
            //get prevDoneTemplates
            foreach ($prevDoneMobileShifts as $prevDoneMobileShift) {
                $prevDoneTemplate = $prevDoneMobileShift->getTemplate();
                if ($prevDoneTemplate) {
                    $this->decreaseInstancesOnTemplate($prevDoneTemplate);
                }
            }
            
        }
        foreach ($fixedMobileShifts as $fixShift) {
            $fixTemplate = $fixShift->getTemplate();
            $this->decreaseInstancesOnTemplate($fixTemplate);
        }
    }

    public function isLocationAppearancesOptimalized()
    {
        foreach ($this->calculateAppearancesByLocation() as $locationAppearance) {
            if ($locationAppearance->getAppearances() < $this->minimumAppearanceNumber) {
                return false;
            }
        }
        return true;
    }

    private function decreaseInstancesOnTemplate(\PlanBundle\Entity\Template $template)
    {
        foreach ($this as $key => $appearanceCounter) {
            if ($appearanceCounter->isOnTemplate($template)) {
                //we have to decrease the usage instance of template
                //NOT REMOVED IF INSTANCES = 0 OR LESS
                $appearancesBefore = $appearanceCounter->getInstances();
                $appearanceCounter->setInstances($appearancesBefore - 1);
            }
        }
    }

    //increment instance if found, and return with the number it can increase appearance number of location
    private function searchAppearanceCounterToIncrementAppearanceOn($locationId)
    {
        //TODO: NE  a legnagyobbat hanem a mindig az eleget találja meg. (kell, a difference)
        foreach ($this as $key => $appearanceCounter) {
            if ($appearanceCounter->getTemplateAppearance()->getLocationId() == $locationId) {
                $this->set($key, $appearanceCounter->setInstances($appearanceCounter->getInstances() + 1));
                return $appearanceCounter->getTemplateAppearance()->getAppearances();
            }
        }
        return 0;
    }

    public function toTemplateIdArray() {
        $templateIds = array();
        foreach ($this as $appCounter) {
            for ($i = 0; $i < $appCounter->getInstances(); $i++) {
                $templateIds[] = $appCounter->getTemplateAppearance()->getTemplateId();
            }
        }
        return $templateIds;
    }
}