<?php

namespace PlanBundle\Schedule\Template\Configuration;

use PlanBundle\Entity\Shift;
use PlanBundle\Schedule\Common\Configuration;

class ShiftConfiguration
{
	protected $periods;

    public function __construct(Configuration $config)
    {
        $this->periods = $config->getShiftPeriods();
    }

    /**
     * Returns true if the given shift begins at morning
     *
     * @return boolean
     */
    public function isMorningShift(Shift $shift)
    {
        $startHour = $shift->getActiveFrom()->format('H');
        return (int)$startHour < $this->periods['afternoon']['start_at'] && (int)$startHour >= $this->periods['morning']['start_at'];
    }

    /**
     * Return true if the given shift begins at afternoon
     *
     * @return boolean
     */
    public function isAfternoonShift(Shift $shift)
    {
        $startHour = $shift->getActiveFrom()->format('H');
        return (int)$startHour < $this->periods['night']['start_at'] && (int)$startHour >= $this->periods['afternoon']['start_at'];
    }

    /**
     * Returns true if the given shift begins at night
     *
     * @return boolean
     */
    public function isNightShift(Shift $shift)
    {
        $startHour = $shift->getActiveFrom()->format('H');
        return (int)$startHour < $this->periods['morning']['start_at'] || (int)$startHour >= $this->periods['night']['start_at'];
    }

    /**
     * Returns a constant integer from the parameters, to decide which type is the shift in.
     * Usage: Preferred Shift.
     *
     * @return integer
     */
    public function getShiftType(Shift $shift)
    {
        if (self::isMorningShift($shift)) {
            return $this->periods['morning']['id'];
        } elseif (self::isAfternoonShift($shift)) {
            return $this->periods['afternoon']['id'];
        } elseif (self::isNightShift($shift)) {
            return $this->periods['night']['id'];
        }
    }

    /**
     * Return the given shift type's label
     *
     * @param  integer $shiftType shift type id
     * @return string             shift type label
     */
    public function getShiftTypeName($shiftType)
    {
    	switch ($shiftType) {
    		case $this->periods['morning']['id']:
    			return $this->periods['morning']['label'];
    			break;
    		case $this->periods['afternoon']['id']:
    			return $this->periods['afternoon']['label'];
    			break;
    		case $this->periods['night']['id']:
    			return $this->periods['night']['label'];
    			break;
    	}
    }

    /**
     *  Returns true if the shift is in the preferred shifts of the template
     */
    public function acceptTemplate(Shift $shift, \PlanBundle\Entity\Template $template)
    {
        return $this->getShiftType($shift) === $template->getPreferredShift();
    }
}