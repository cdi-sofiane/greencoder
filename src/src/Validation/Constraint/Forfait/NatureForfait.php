<?php

namespace App\Validation\Constraint\Forfait;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NatureForfait extends Constraint
{
    public $message = 'field {{string}} must have some constraints';

    public function validatedBy()
    {
        return self::class . 'Validator';
    }
}