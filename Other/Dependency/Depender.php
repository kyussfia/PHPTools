<?php

namespace PlanBundle\Schedule\Dependency;

/**
 * Description of Depender
 */
interface Depender
{
    /**
     * @return \PlanBundle\Schedule\Dependency\DependencyInterface[]
     */
    public function getDependencies();
}
