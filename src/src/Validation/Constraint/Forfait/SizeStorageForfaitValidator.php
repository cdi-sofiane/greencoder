<?php

namespace App\Validation\Constraint\Forfait;


use App\Entity\Forfait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SizeStorageForfaitValidator extends ConstraintValidator
{
    private $em;

    private $definitions;

    public function __construct(EntityManagerInterface $em, ContainerInterface $definitions)
    {

        $this->em = $em;
        $this->definitions = $definitions;
    }


    public function validate($value, Constraint $constraint)
    {

        switch ($this->context->getObject()->getNature()) {
            case Forfait::NATURE_HYBRID:
            case Forfait::NATURE_STOCKAGE:

                if (is_null($this->context->getObject()->getSizeStorage()) || $this->context->getObject()->getSizeStorage() <= 0) {
                    $constraint->message = "cant' be null or 0";
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;

            case Forfait::NATURE_ENCODAGE:
                if ($this->context->getGroup() == "update") {
                    $this->context->getObject()->setSizeStorage(null);
                }
                if (!is_null($this->context->getObject()->getSizeStorage()) || ($this->context->getObject()->getSizeStorage() != 0)) {
                    $constraint->message = "must be null or 0";
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;
        }

    }
}