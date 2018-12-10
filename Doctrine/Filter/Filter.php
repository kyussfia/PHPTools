<?php

namespace Slametrix\Doctrine\ORM\Filter;

/**
 * Value object
 *
 * @author bordacs
 */
class Filter implements FilterInterface
{
    const NE          = 'ne';
    const NEQ         = 'neq';
    const EQ          = 'eq';
    const EQUAL       = 'eq';
    const LT          = 'lt';
    const LTE         = 'lte';
    const GT          = 'gt';
    const GTE         = 'gte';
    const LIKE        = 'like';
    const IN          = 'in';
    const NOT_IN      = 'notIn';
    const IS_NULL     = 'isNull';
    const IS_NOT_NULL = 'isNotNull';

    private $property;
    private $value;
    private $operator;

    /**
     * @param string | \Slametrix\Doctrine\ORM\Filter\Filter $property
     * @param string $value If @param $property is object, the parameter value will be omitted.
     * @param string $operator If @param $property is object, the parameter value will be omitted.
     */
    public function __construct($property, $value = null, $operator = null)
    {
        if ($property instanceof Filter) {
            $this->property = $property->getProperty();
            $this->value = $property->getValue();
            $this->operator = $property->getOperator();
        }
        else {
            $this->property = $property;
            $this->value = $value;
            $this->operator = $operator;
        }
    }

    public static function create($property, $value = null, $operator = null)
    {
        return new static($property, $value, $operator);
    }

    /**
     * Alias of Filter::neq().
     *
     * @param string $property
     * @param mixed $value
     * @return AppBundle\Doctrine\Query\Filter
     */
    public static function ne($property, $value)
    {
        return static::neq($property, $value);
    }

    public static function neq($property, $value)
    {
        return static::create($property, $value, 'neq');
    }

    public static function eq($property, $value)
    {
        return static::create($property, $value, 'eq');
    }

    public static function equal($property, $value)
    {
        return static::create($property, $value, 'eq');
    }

    public static function lt($property, $value)
    {
        return static::create($property, $value, 'lt');
    }

    public static function lte($property, $value)
    {
        return static::create($property, $value, 'lte');
    }

    public static function gt($property, $value)
    {
        return static::create($property, $value, 'gt');
    }

    public static function gte($property, $value)
    {
        return static::create($property, $value, 'gte');
    }

    public static function like($property, $value)
    {
        return static::create($property, (string)$value, 'like');
    }

    public static function in($property, $value)
    {
        return static::create($property, $value, 'in');
    }

    public static function notIn($property, $value)
    {
        return static::create($property, $value, 'notIn');
    }

    public static function isNull($property)
    {
        return static::create($property, null, 'isNull');
    }

    public static function isNotNull($property)
    {
        return static::create($property, null, 'isNotNull');
    }

    public static function validAt($property, $value)
    {
        return new TimePeriodFilter($property, $value, 'valid');
    }

    public static function activeAt($property, $value)
    {
        return new TimePeriodFilter($property, $value, 'active');
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visit($this);
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function getValue()
    {
        if (self::isValueInDateTimeFormat()) {
             return new \DateTime($this->value);
        }

        return $this->value;
    }

    private function isValueInDateTimeFormat()
    {
        return is_string($this->value)
            && (
                preg_match('/'.
                '^\d{4}\-\d{2}-\d{2}'.
                '(\s\d{2}\:\d{2}\:\d{2})?'.
                '$/', $this->value) ||
                preg_match('/'.
                '^\d{4}\-\d{2}-\d{2}'.
                'T\d{2}\:\d{2}\:\d{2}'.
                '((\+|\-)\d{2}\:\d{2})?'.
                '$/', $this->value)
                );
    }

    /**
     * Returns expression converter class, used to convert filter into
     * Doctrine query expression.
     *
     * @return string
     */
    public function getConvertedBy()
    {
        return FilterConverter::class;
    }
}
