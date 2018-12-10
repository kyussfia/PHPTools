<?php

namespace PlanBundle\Schedule\Dependency;

/**
 * Description of AbstractDependency
 */
abstract class AbstractDependency implements DependencyInterface
{
    protected $depender;
    protected $dependee;

    /**
     * @param Object $dependerObject
     * @param Object $dependeeObject
     */
    public function __construct($dependerObject, $dependeeObject)
    {
        $supportedClasses = $this->supportsClasses();

        if ($dependeeObject !== null && $supportedClasses[1] != get_class($dependeeObject) && !is_subclass_of($dependeeObject, $supportedClasses[1])) {
            throw new \InvalidArgumentException('Dependee object must be instance of '.$supportedClasses[1]. ', given '.get_class($dependeeObject));
        }

        if ($supportedClasses[0] != get_class($dependerObject) && !is_subclass_of($dependerObject, $supportedClasses[0])) {
            throw new \InvalidArgumentException('Depender object must be instance of '.$supportedClasses[0]. ', given '.get_class($dependerObject));
        }

        $this->depender = $dependerObject;
        $this->dependee = $dependeeObject;
    }

    /**
     * {@inheritDoc}
     */
    public function getObject()
    {
        return $this->depender;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependee()
    {
        return $this->dependee;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function check(DependencyContext $context);

    /**
     * {@inheritDoc}
     */
    abstract public function supportsClasses();

    /**
     * {@inheritDoc}
     */
    public function supportsScenarios()
    {
        return array();
    }
}