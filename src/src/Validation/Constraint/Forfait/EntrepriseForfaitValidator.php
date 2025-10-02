<?php

namespace App\Validation\Constraint\Forfait;

use App\Entity\Forfait;
use App\Helper\JsonDumper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntrepriseForfaitValidator extends ConstraintValidator
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
                $this->context->getObject()->setIsEntreprise(false);
                return;
            case Forfait::NATURE_ENCODAGE:
                $types = array(Forfait::TYPE_CREDIT, Forfait::TYPE_ABONNEMENT);
                if (!in_array($this->context->getObject()->getType(), $types)) {
                    $this->context->getObject()->setIsEntreprise(false);
                }

                if ($this->context->getObject()->getIsEntreprise() === null) {
                    $constraint->message = "must be true or false";
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;

            case Forfait::NATURE_STOCKAGE:
                if ($this->context->getObject()->getIsEntreprise() === null) {

                    $constraint->message = "must be true or false";
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;

        }

    }
}