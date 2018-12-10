<?php

namespace PlanBundle\Schedule\Dependency;

use PlanBundle\Schedule\Common\Configuration;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Description of Manager
 */
class DependencyManager
{
    /**
     * @var \PlanBundle\Schedule\Common\Configuration
     */
    private $configuration;

    /**
     * @var \Doctrine\Common\Persistence\ManagerRegistry
     */
    private $registry;

    /**
     * @param \PlanBundle\Schedule\Common\Configuration $configuration
     */
    public function __construct(Configuration $configuration, ManagerRegistry $registry)
    {
        $this->configuration = $configuration;
        $this->registry = $registry;
    }

    /**
     * @param \PlanBundle\Schedule\Dependency\Depender $depender
     * @param \PlanBundle\Schedule\Dependency\DependencyContext $context
     * @return mixed[]  Dependency errors
     */
    public function checkDependencies(Depender $depender, $deep = false, DependencyContext $context = null)
    {
        if (null === $context) {
            $context = new DependencyContext();
        }

        $walker = new DependencyWalker($context, $this->registry);

        $context->initialize($walker, $this->configuration);

        $walker->start($depender, $deep);

        return $context->getErrors();
    }
}
