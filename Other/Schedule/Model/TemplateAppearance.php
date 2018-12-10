<?php

namespace PlanBundle\Schedule\Template\Model;

class TemplateAppearance {

    private $templateId;
    private $locationId;
    private $appearances;


    public function __construct($templateId, $locationId, $appearances)
    {
        $this->templateId = $templateId;
        $this->locationId = $locationId;
        $this->appearances = $appearances;
    }

    public function getAppearances()
    {
        return $this->appearances;
    }

    public function getTemplateId()
    {
        return $this->templateId;
    }

    public function getLocationId()
    {
        return $this->locationId;
    }
}