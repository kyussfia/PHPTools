<?php

namespace App\Entity\VirtualFilters;

class ExpenseItemHasInvoiceFilter extends \Slametrix\Doctrine\ORM\Filter\ArrayFilter
{
    protected function isFilterValueValid()
    {
        return is_bool($this->getFilterValues());
    }
}