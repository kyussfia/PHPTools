<?php

namespace App\Entity\VirtualFilters;

class InvoiceCanBePaidFilter extends \Slametrix\Doctrine\ORM\Filter\ArrayFilter
{
    protected function isValueValid()
    {
        return is_bool($this->getFilterValues());
    }

}