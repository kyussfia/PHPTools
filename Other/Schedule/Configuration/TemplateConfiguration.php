<?php

namespace PlanBundle\Schedule\Template\Configuration;

use PlanBundle\Entity\Shift;
use PlanBundle\Schedule\Common\Configuration;

class TemplateConfiguration
{
    protected $options;

    public function __construct(Configuration $config)
    {
        $this->options = $config->getTemplateOptions();
    }

    public function getMinimumAppearancesOnLocations()
    {
        return (int)$this->options['minimum_appearances_on_locations'];
    }

    public function getMinimumDistanceBetweenMeasures()
    {
        return (int)$this->options['minimum_distance_between_measures'];
    }
}
