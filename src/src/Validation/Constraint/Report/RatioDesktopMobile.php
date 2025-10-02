<?php

namespace App\Validation\Constraint\Report;

use Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 */
class RatioDesktopMobile extends Constraint
{

    public $message = 'field {{string}} must be unique';

    public function validatedBy()
    {

        return self::class . 'Validator';
    }
}
