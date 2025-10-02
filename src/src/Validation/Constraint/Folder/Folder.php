<?php

namespace App\Validation\Constraint\Folder;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Folder extends Constraint
{
    public $message = 'field {{string}} must be unique at this folder level';

    public function validatedBy()
    {

        return self::class . 'Validator';
    }
}
