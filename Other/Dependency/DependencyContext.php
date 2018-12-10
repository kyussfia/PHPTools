<?php

namespace PlanBundle\Schedule\Dependency;

use PlanBundle\Schedule\Common\Configuration;

class DependencyContext
{
    /**
     * @var \PlanBundle\Schedule\Dependency\DependencyWalker
     */
    private $walker;

    /**
     * @var \PlanBundle\Schedule\Common\Configuration
     */
    private $configuration;

    /**
     * @var string[]
     */
    private $errors;

    /**
     *
     * @var bool
     */
    private $initialized;

    /**
     *
     * @var \PlanBundle\Schedule\Dependency\AbstractScenario
     */
    private $scenario;

    /**
     * @var array
     */
    private $params;

    /**
     * @param \PlanBundle\Schedule\Dependency\AbstractScenario|null $scenario
     * @param array $params
     */
    public function __construct(AbstractScenario $scenario = null, array $params = null)
    {
        $this->initialized = false;

        $this->scenario = $scenario;

        $this->errors = array();

        $this->params = $params;
    }

    /**
     * @param \PlanBundle\Schedule\Dependency\AbstractScenario|null $scenario
     */
    public function create(AbstractScenario $scenario = null)
    {
        return new self($scenario);
    }

    /**
     * @param \PlanBundle\Schedule\Common\Configuration $configuration
     */
    public function initialize(DependencyWalker $walker, Configuration $configuration)
    {
        if ($this->initialized) {
            throw new \LogicException('This context was already initialized, and cannot be re-used.');
        }

        $this->initialized = true;

        $this->walker = $walker;
        $this->configuration = $configuration;
    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * @return \PlanBundle\Schedule\Dependency\Depender
     */
    public function getRoot()
    {
        if (!$this->initialized) {
            throw new \LogicException('To get root object, the context must be initialized.');
        }
        return $this->walker->getRoot();
    }

    /**
     * @return \PlanBundle\Schedule\Common\Configuration
     */
    public function getConfiguration()
    {
        if (!$this->initialized) {
            throw new \LogicException('Context not yet initialized.');
        }

        return $this->configuration;
    }

    /**
     * @return bool
     */
    public function hasScenario()
    {
        return null !== $this->scenario;
    }

    /**
     * @return \PlanBundle\Schedule\Dependency\AbstractScenario
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @param string $error
     */
    public function addError($error)
    {
        if(!in_array($error, $this->errors)) {
            $this->errors[] = $error;
        }
    }

    /**
     * @param string[] $errors
     * @return void
     */
    public function addErrors($errors)
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}
