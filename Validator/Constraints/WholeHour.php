<?php

namespace Slametrix\Validator\Constraints;

/**
 * @Annotation
 */
class WholeHour extends Divisible
{
    public function __construct($options = array())
    {
        parent::__construct(array_replace((array)$options, array(
            'message' => 'validator:whole_hour',
            'divisor' =>  3600
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
