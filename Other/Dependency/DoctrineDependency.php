<?php

namespace PlanBundle\Schedule\Dependency;

use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Description of DoctrineDependency
 */
abstract class DoctrineDependency extends AbstractDependency
{
    protected $registry;

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
}
