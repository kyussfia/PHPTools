<?php

namespace PlanBundle\Schedule\Template\Model;

use PlanBundle\Entity\Shift;
use PlanBundle\Entity\Template;

/**
 * TemplateShift: A dumy class to represent a shift.
 */
class TemplateShift
{
    private $shift;
    private $template;
    private $required; //this pair is required if it casnnot be changed -> minimum X appearances

    private $templateId;
    private $shiftId;
    private $from;
    private $to;

    public function __construct(Shift $shift, Template $template = null, $required = false)
    {
        $this->shift = $shift;
        $this->template = $template;
        $this->required = $required;
        $this->templateId = null != $template ? $template->getId() : null;
        $this->shiftId = $shift->getId();
        $this->to = $shift->getActiveTo()->format("Y-m-d H:i:s");
        $this->from = $shift->getActiveFrom()->format("Y-m-d H:i:s");
    }

    public function getShift()
    {
        return $this->shift;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function isEmptyShift()
    {
        return empty($this->template);
    }

    public function getId()
    {
        return $this->shift->getId();
    }

    public function getActivityPeriod()
    {
        return $this->shift->getActivityPeriod();
    }

    public function isRequired()
    {
        return $this->required;
    }
}