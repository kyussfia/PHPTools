<?php

namespace Slametrix\Doctrine\ORM\Sorter;

use Slametrix\Doctrine\ORM\Sorter\SorterCollection;

/**
 * Class ArraySorter : Sort array of entities by virtual or pre-calculated attribute.
 * Usage: initFromSorterCollection with the name of the attribute in SorterCollection (This must be before doctrine sorting, to avoid errors on property check)
 * After doctrine sorting : use sort on not paged and not limited list
 *
 * @author Mark Mikus
 * @package Slametrix\Doctrine\ORM\Sorter
 */
class ArraySorter
{
    protected $sorter;

    protected $sortableValues;

    public static function initFromSorterCollection(string $name, ?SorterCollection &$sorter)
    {
        $used = null;
        if (null !== $sorter)
        {
            $used = $sorter->get($name);
            $sorter->remove($name);
        }
        return new static($used);
    }

    public function sort(array &$what, string $method)
    {
        $this->initSortableValues($what, $method);
        $this->sortIfUsed($what);
    }

    protected function __construct($sorter)
    {
        $this->sorter = $sorter;
        $this->sortableValues = array();
    }

    private function initSortableValues(array $elements, string $method)
    {
        $valueGetter = null;
        foreach ($elements as $element)
        {
            if (empty($valueGetter)) {
                $valueGetter = new \ReflectionMethod(get_class($element), $method);
            }
            $this->sortableValues[] = $valueGetter->invoke($element);
        }
    }

    private function getSortDirectionAsString()
    {
        return strtoupper($this->sorter->getDirection());
    }

    private function performSort(array &$sortable)
    {
        array_multisort($this->sortableValues, constant("SORT_".$this->getSortDirectionAsString()), $sortable);
    }

    private function sortIfUsed(array &$measurepoints)
    {
        if ($this->isUsed())
        {
            $this->performSort($measurepoints);
        }
    }

    private function isUsed()
    {
        return null !== $this->sorter && !empty($this->sorter);
    }
}