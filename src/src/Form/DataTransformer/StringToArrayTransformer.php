<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StringToArrayTransformer implements DataTransformerInterface
{
    public function transform($array)
    {
        // Transform the array to a comma-separated string
        return is_array($array) ? implode(',', $array) : '';
    }

    public function reverseTransform($string)
    {
        // Transform the comma-separated string to an array
        return $string !== null ? explode(',', $string) : null;
    }
}
