<?php

namespace App\Entity\VirtualFilters;

class InvoiceIsSettledFilter extends \Slametrix\Doctrine\ORM\Filter\ArrayFilter
{
    protected function isValueValid()
    {
        return is_bool($this->getFilterValues());
    }
}