<?php

namespace Slametrix\Validator;

/**
 * Description of ValidateByTrait
 * */
trait ValidateByTrait
{
    public function validatedBy()
    {
        return str_replace('Slametrix\\', 'Symfony\\Component\\', static::class).'Validator';
    }
}
