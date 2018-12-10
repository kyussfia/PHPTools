<?php

namespace PlanBundle\Schedule\Template\Model;

class LocationAppearanceCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    public function __construct(array $locationsWithAppearances = array())
    {
        $elements = array();
        foreach ($locationsWithAppearances as $locationWithAppearance) {
            $elements[] = new LocationAppearance($locationWithAppearance[0], $locationWithAppearance[1]);
        }
        parent::__construct($elements);
    }

    public function indexOfLocationId($locationId)
    {
        foreach ($this as $key => $locationAppearance) {
            if ($locationAppearance->hasLocationId($locationId)) {
                return $key;
            }
        }
        return FALSE;
    }
}