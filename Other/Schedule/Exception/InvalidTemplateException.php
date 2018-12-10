<?php

namespace PlanBundle\Schedule\Template\Exception;

use PlanBundle\Schedule\Common\Exception\ScheduleException;

/**
 * Description of InvalidTemplateException
 */
class InvalidTemplateException extends ScheduleException
{
    private $data;

    public function setData($data)
    {
        $this->data = $data;
    }
    public function getData()
    {
        return $this->data;
    }
}
