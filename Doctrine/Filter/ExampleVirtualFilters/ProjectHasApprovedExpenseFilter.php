<?php

namespace App\Entity\VirtualFilters;

class ProjectHasApprovedExpenseFilter extends \Slametrix\Doctrine\ORM\Filter\ArrayFilter
{
    protected function isFilterValueValid()
    {
        return is_bool($this->getFilterValues());
    }
}