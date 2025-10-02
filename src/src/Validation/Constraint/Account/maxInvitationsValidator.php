<?php

namespace App\Validation\Constraint\Account;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class maxInvitationsValidator extends ConstraintValidator
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



        $entity = $this->em->getRepository(get_class($this->context->getRoot()))->findOneBy(['uuid' => $this->context->getRoot()->getUuid()]);
        $countMembersInAccount = $entity->getUserAccountRole(function ($userAccountRole) {
            return $userAccountRole;
        });

        if ($countMembersInAccount->count() > $this->context->getRoot()->getMaxInvitations()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{string}}', $value)
                ->addViolation();
        }
    }
}
