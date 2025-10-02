<?php

namespace App\Validation\Constraint\Forfait;

use App\Entity\Forfait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ActiveForfaitValidator extends ConstraintValidator
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
                $entity = $this->em->getRepository(get_class($this->context->getRoot()))->findOneBy(['type' => $this->context->getObject()->getType(), 'isActive' => true]);
                if ($entity == null) {
                    return;
                }
                if ($entity->getUuid() === $this->context->getObject()->getUuid()) {
                    return;
                }
                if (($entity != null && $this->context->getObject()->getIsActive() === true)) {
                    $constraint->message = "can't have more than one active " . Forfait::NATURE_HYBRID . ' package';
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{string}}', $value)
                        ->addViolation();
                }
                return;

        }

    }
}