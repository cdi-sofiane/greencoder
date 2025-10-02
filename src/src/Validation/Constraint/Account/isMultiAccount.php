<?php

namespace App\Validation\Constraint\Account;

use Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 */
class isMultiAccount extends Constraint
{
    public $message = 'field {{string}} must be unique';

    public function validatedBy()
    {
        return self::class . 'Validator';
    }
}
