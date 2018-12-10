<?php

namespace App\Entity\VirtualFilters;

class InvoiceHasItemsFilter extends \Slametrix\Doctrine\ORM\Filter\ArrayFilter
{
    protected function isValueValid()
    {
        return is_bool($this->getFilterValues());
    }
}