<?php

namespace PlanBundle\Schedule\Template\Model;

use PlanBundle\Schedule\Template\Model\Appearance;

class AppearanceCounter {
    private $templateAppearance;
    private $instances;

    public function __construct(TemplateAppearance $templateAppearance, int $instances = 1) {
        $this->templateAppearance = $templateAppearance;
        $this->instances = $instances;
    }


    public function getTemplateAppearance()
    {
        return $this->templateAppearance;
    }

    public function getInstances()
    {
        return $this->instances;
    }

    public function setInstances(int $instances)
    {
        $this->instances = $instances;
        return $this;
    }

    public function isOnTemplate(\PlanBundle\Entity\Template $template)
    {
        if ($this->templateAppearance->getTemplateId() == $template->getId()) {
            return true;
        }
        return false;
    }
}