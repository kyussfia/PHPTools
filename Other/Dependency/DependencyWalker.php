<?php

namespace PlanBundle\Schedule\Dependency;

use PlanBundle\Schedule\Dependency\Exception\CircularDependencyFoundException;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Description of DependecyWalker
 */
class DependencyWalker
{
    /**
     *
     * @var \PlanBundle\Schedule\Dependency\Depender
     */
    private $root;

    /**
     * @var \PlanBundle\Schedule\Dependency\DependencyContext
     */
    private $context;

    /**
     * @var \Doctrine\Common\Persistence\ManagerRegistry
     */
    private $registry;

    /**
     * Key is object id (spl_object_hash).
     *
     * @var array
     */
    private $visited;

    /**
     * @param \PlanBundle\Schedule\Dependency\Depender
     * @param \PlanBundle\Schedule\Dependency\DependencyContext
     */
    public function __construct(DependencyContext $context, ManagerRegistry $registry)
    {
        $this->context = $context;
        $this->registry = $registry;
        $this->visited = array();
    }

    /**
     * @param \PlanBundle\Schedule\Dependency\Depender
     * @param bool $deep
     * @throws \PlanBundle\Schedule\Dependency\Exception\CircularDependencyException
     * @return void
     */
    public function start(Depender $depender, $deep = false)
    {
        $this->root = $depender;

        $this->walk($depender, $deep);
    }

    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param \PlanBundle\Schedule\Dependency\Depender
     * @param bool $deep
     * @throws \PlanBundle\Schedule\Dependency\Exception\CircularDependencyException
     * @return void
     */
    private function walk(Depender $depender, $deep)
    {
        $oid = spl_object_hash($depender);

        if (array_key_exists($oid, $this->visited)) {
            throw new CircularDependencyFoundException();
        }

        $this->visited[$oid] = $depender;

        $dependencies = $depender->getDependencies();

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof DoctrineDependency) {
                $dependency->setRegistry($this->registry);
            }

            $scenario = null;
            if ($this->context->hasScenario()) {
                $scenario = $this->context->getScenario();
            }

            if (null !== $scenario && in_array(get_class($scenario), $dependency->supportsScenarios())) {
                $scenario->execute($dependency, $this->context);
            }
            else {
                $dependency->check($this->context);
            }

            if ($deep && $dependency->getDependee() instanceof Depender) {
                $this->walk($dependency->getDependee(), $deep);
            }
        }
    }
}
