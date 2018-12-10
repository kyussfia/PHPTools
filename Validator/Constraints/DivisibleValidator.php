<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DivisibleValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof \DateTime) {
            $value = $value->getTimestamp();
        }

        if (0 != $value % $constraint->divisor) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)->addViolation();
            } else {
                $this->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}
