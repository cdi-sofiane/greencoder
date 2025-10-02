<?php

namespace App\Validation\Constraint\Forfait;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PriceForfait extends Constraint
{
    public $message = 'field {{string}} must in Euro ';

    public function validatedBy()
    {
        return self::class . 'Validator';
    }
}