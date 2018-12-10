<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class Divisible extends Constraint
{
    public $message = 'validator:divisible';
    public $divisor;

    public function __construct($options)
    {
        if ($options['divisor'] && (int)$options['divisor'] != 0) {
            $this->divisor = $options['divisor'];
        } else {
            throw new MissingOptionsException(sprintf('Option "divisor" must be given for constraint %s and must not be equal with zero', __CLASS__), array('divisor'));
        }

        parent::__construct($options);
    }
}
