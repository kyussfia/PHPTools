<?php

namespace Slametrix\Validator\Constraints;

/**
 * @Annotation
 */
class TenMinutes extends Divisible
{
    public function __construct($options = array())
    {
        parent::__construct(array_replace((array)$options, array(
            'message' => 'validator:ten_minutes',
            'divisor' =>  600
        )));
    }
   
    /**
     * {@inheritDoc}
     */   
    public function validatedBy()
    {
        return 'Slametrix\Validator\Constraints\DivisibleValidator';
    }
}
