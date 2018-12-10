<?php

namespace PlanBundle\Schedule\Dependency;

/**
 * Description of AbstractScenario
 */
abstract class AbstractScenario
{
    abstract protected function getRules();

    public function execute($dependency, DependencyContext $context)
    {
        $classes = $dependency->supportsClasses();

        $rules = $this->getRules();

        if (!array_key_exists($classes[0], $rules)) {
            return;
        }

        foreach ($rules[$classes[0]] as $dependee => $rules) {
            if ($dependee == $classes[1]) {
                foreach ($rules as $rule) {
                    call_user_func(array($dependency, 'rule'.ucfirst($rule)), $context);
                }
            }
        }
    }

}
