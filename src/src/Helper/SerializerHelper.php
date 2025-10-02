<?php

namespace App\Helper;

use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerHelper
{
    protected $serialization;
    protected $normalizable;

    public function __construct(SerializerInterface $serializer, NormalizerInterface $normalizable)
    {
        $this->serialization = $serializer;
        $this->normalizable = $normalizable;
    }

    public function serialize($item, $format = '', array $context = [])
    {
        return $this->serialization->serialize($item, $format, $context);

    }

    public function deserialize($item, $type, $format = '', array $context = [])
    {
        return $this->serialization->deserialize($item, $format, $context);

    }


    public function normalize($item, $format = '', array $context = [])
    {
        return $this->normalizable->normalize($item, $format, $context);
    }

}