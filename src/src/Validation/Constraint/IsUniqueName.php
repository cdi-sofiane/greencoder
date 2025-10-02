<?php

namespace App\Validation\Constraint;

use Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 */
class IsUniqueName extends Constraint
{
    public $message = 'field {{string}} must be unique';

    public function validatedBy()
    {
        return self::class . 'Validator';
    }
}