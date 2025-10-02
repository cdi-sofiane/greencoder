<?php

namespace App\Validation\Constraint\Account;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class isMultiAccountValidator extends ConstraintValidator
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

        if (!is_bool($value)) {
            return;
        }

        $entity = $this->em->getRepository(get_class($this->context->getRoot()))->findOneBy(['email' => $this->context->getRoot()->getEmail()]);

        if ($entity->getUsages() == Account::USAGE_INDIVIDUEL ) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{string}}', $value)
                ->addViolation();
        }
    }
}
