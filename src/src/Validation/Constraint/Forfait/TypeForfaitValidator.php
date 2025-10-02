<?php

namespace App\Validation\Constraint\Forfait;

use App\Entity\Forfait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TypeForfaitValidator extends ConstraintValidator
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
        if (null === $value || '' === $value) {
            return;
        }
        switch ($this->context->getObject()->getNature()) {
            case Forfait::NATURE_HYBRID:

                if ($this->context->getObject()->getType() != Forfait::TYPE_GRATUIT ) {
                    $constraint->message = "must be " . Forfait::TYPE_GRATUIT ;
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;
            case Forfait::NATURE_ENCODAGE:
                if ($this->context->getObject()->getType() == Forfait::TYPE_GRATUIT) {
                    $constraint->message = "type must be " . Forfait::TYPE_ABONNEMENT . ' ' . Forfait::TYPE_CREDIT . ' ' . Forfait::TYPE_ONESHOT;
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;
            case Forfait::NATURE_STOCKAGE:

                if ($this->context->getObject()->getType() != Forfait::TYPE_ABONNEMENT) {
                    $constraint->message = "must be " . Forfait::TYPE_ABONNEMENT;
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;

        }

    }
}