<?php

namespace App\Validation\Constraint\Forfait;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ActiveForfait extends Constraint
{
    public $message = 'field {{string}} must be in minutes ';

    public function validatedBy()
    {
        return self::class . 'Validator';
    }
}