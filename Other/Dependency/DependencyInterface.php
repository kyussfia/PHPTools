<?php

namespace PlanBundle\Schedule\Dependency;

/**
 * Description of DependencyInterface
 */
interface DependencyInterface
{
    public function check(DependencyContext $context);

    /**
     * @return Object  The depender object.
     */
    public function getObject();

    /**
     * @return Object  The dependee object.
     */
    public function getDependee();

    /**
     * @return string[]  FQCN of supported depender and dependee objects
     */
    public function supportsClasses();

    /**
     * @return string[]  FQCN of supported scenario objects
     */
    public function supportsScenarios();
}
