<?php

namespace App\Validation\Constraint\Forfait;

use App\Entity\Forfait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NatureForfaitValidator extends ConstraintValidator
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        switch ($value) {
            case Forfait::NATURE_HYBRID:
                $this->context->getObject()->setPrice(0);

                return;
            case Forfait::NATURE_STOCKAGE:
            case Forfait::NATURE_ENCODAGE:
                $this->context->getObject()->setIsAutomatic(false);

                return;
        }
    }


}