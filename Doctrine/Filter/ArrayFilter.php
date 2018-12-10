<?php

namespace Slametrix\Doctrine\ORM\Filter;

use Slametrix\Doctrine\ORM\Filter\FilterCollection;

/**
 * Class ArrayFilter : Filter array of entities on virtual or pre-calculated attribute.
 * Usage: initFromFilterCollection with the name of the attribute in Filter, (This must be before doctrine filtering, to avoid errors on property check)
 * After doctrine filtering : use filter method, and than finally needed to paging and limiting results manually.
 * Paging: You can use filter method, and then use page method, to filter the result and page returns with the filtered result or use filterAndPage method to get rid of both,
 * and this method is handle data arrays as a reference, so after calling this the result will be in the input data array.
 *
 * @author Mark Mikus
 * @package Slametrix\Doctrine\ORM\Filter
 */
class ArrayFilter
{
    private $filter;

    public static function initFromFilterCollection(string $name, ?FilterCollection &$filter)
    {
        if (null !== $filter)
        {
            $usedFilter = $filter->get($name);
            $filter->remove($name);
        }
        else {
            $usedFilter = null;
        }
        return new static($usedFilter);
    }

    public function filter(array &$filterable, string $getter, int &$total)
    {
        $this->filterIfUsed($filterable, $getter, $total);
    }

    public function page(array $items, ?int $offset, ?int $limit) : array
    {
        return array_slice($items, $offset, $limit);
    }

    public function filterAndPage(array &$filterable, string $getter, int &$total, ?int $offset, ?int $limit)
    {
        $this->filter($filterable, $getter, $total);
        $filterable = $this->page($filterable, $offset, $limit);
    }

    /**
     * Method to implement additional validity rules for the specified field of this filter.
     *
     * @return mixed
     */
    protected function isFilterValueValid()
    {
        return true;
    }

    protected function __construct($filter)
    {
        $this->filter = $filter;
    }

    protected function getFilterValues()
    {
        return $this->filter->getValue();
    }

    private function performFilter(array $what, $getterName)
    {
        $first = reset($what);
        $getterMethod = new \ReflectionMethod(get_class($first), $getterName);
        return array_values(array_filter($what, function($e) use ($getterMethod) {
            return $this->getCondition($getterMethod->invoke($e));
        }));
    }

    private function filterIfUsed(array &$items, string $getter, int &$total)
    {
        if ($this->isUsed())
        {
            if (!empty($items)) {
                $items = $this->performFilter($items, $getter);
            }
            $total = count($items);
        }
    }

    private function isUsed()
    {
        return null !== $this->filter && !empty($this->filter) && $this->isFilterValueValid();
    }

    private function getCondition($fieldValue)
    {
        $operator = strtolower($this->filter->getOperator());
        $filterValue = $this->filter->getValue();
        switch ($operator)
        {
            case '==':
            case 'eq':
                return $filterValue == $fieldValue;
            default:
                throw new \RuntimeException(get_class($this)." does not support ". $operator ." type filters!");
        }
    }
}