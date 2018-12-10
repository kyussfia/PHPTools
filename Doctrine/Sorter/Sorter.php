<?php

namespace Slametrix\Doctrine\ORM\Sorter;

/**
 * @author bordacs
 */
class Sorter
{
    private $property;
    private $direction;

    const ASC = 'ASC';
    const DESC = 'DESC';

    /**
     * @param string | \Slametrix\Doctrine\ORM\Sorter\Sorter $property
     * @param string $direction If @param $property is object, the parameter value will be omitted.
     */
    public function __construct($property, $direction = null)
    {
        if ($property instanceof Sorter) {
            $this->property = $property->getProperty();
            $this->direction = $property->getDirection();
        }
        else {
            $this->property = $property;
            $this->direction = strtoupper($direction);
        }
    }

    public static function create($property, $direction)
    {
        return new static($property, $direction);
    }

    public static function asc($property)
    {
        return new static($property, self::ASC);
    }

    public static function desc($property)
    {
        return new static($property, self::DESC);
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visit($this);
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getDirection()
    {
        return $this->direction;
    }
}
