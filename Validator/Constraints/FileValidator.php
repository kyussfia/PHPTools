<?php

namespace Slametrix\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator as BaseFileValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class FileValidator extends BaseFileValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof File) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\File');
        }
        
    	if (!preg_match($constraint->fileNamePattern, $value->getClientOriginalName())) {
    		$this->context->buildViolation($constraint->invalidFileNameMessage)->addViolation();
    	}

    	if (!preg_match($constraint->extension, $value->getClientOriginalExtension())) {
            $this->context->buildViolation($constraint->uploadExtensionErrorMessage)->addViolation();
        }

    	parent::validate($value, $constraint);
    }
}