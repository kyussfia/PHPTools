<?php

namespace PlanBundle\Schedule\Template\Model;

class LocationAppearance {

    private $location;

    private $appearances;

    public function __construct($location, $appearances = 1)
    {
        $this->location = $location;
        $this->appearances = $appearances;
    }

    public function setAppearances(int $apps)
    {
        $this->appearances = $apps;
        return $this;
    }

    public function getAppearances()
    {
        return $this->appearances;
    }

    public function getLocationId()
    {
        if (is_scalar($this->location)) {
            return $this->location;
        }
        return $this->location->getId();
    }

    public function hasLocationId($locationId)
    {
        if (is_scalar($this->location)) {
            return $this->location == $locationId;
        }
        return $this->location->getId() == $locationId;
    }

}