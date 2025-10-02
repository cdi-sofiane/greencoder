<?php

namespace App\Validation\Constraint\Account;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AccountUsageTypeValidator extends ConstraintValidator
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



        if (Account::USAGE_PRO === $this->context->getRoot()->getUsages()) {
           
            if ($this->context->getRoot()->getCompany() == null)
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{string}}', $value)
                    ->addViolation();
        }
    }
}
