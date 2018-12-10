<?php

namespace PlanBundle\Schedule\Template\Comparator;

abstract class Comparator
{
    protected $strictMatch; //szigorú vizsgálat

    protected $shift;

    protected $template;

    abstract public function strictRule();

    abstract public function baseRule();

    public function isMatch()
    {
        if ($this->strictMatch) {
            return $this->strictRule() && $this->baseRule();
        }
        return $this->baseRule();
    }
}