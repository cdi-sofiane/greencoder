<?php

namespace App\Validation\Constraint;

use App\Entity\Forfait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsUniqueNameValidator extends ConstraintValidator
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
        $entity = $this->em->getRepository(get_class($this->context->getRoot()))->findOneBy(['name' => $value]);

        if ($entity != null) {
            if ( $entity->getId() === $this->context->getRoot()->getId()) {
                return;
            }
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{string}}', $value)
                ->addViolation();
        }

    }


}