<?php

namespace App\Validation\Constraint\Report;

use App\Entity\Forfait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function PHPUnit\Framework\isType;

class RatioDesktopMobileValidator extends ConstraintValidator
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {

        $this->em = $em;
    }


    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value || is_string($value)) {
            return;
        }


        if (!is_integer($value)) {
            throw new UnexpectedValueException($value, 'integer');
        }
        if (is_string($this->context->getRoot()->getDesktopRepartition()) || is_string($this->context->getRoot()->getMobileRepartition())) {
            throw new UnexpectedValueException($value, 'integer');
        }

        $totalRatio = 100;
        switch ($this->context->getPropertyName()) {
            case 'mobileRepartition':

                $totalRatio = $this->context->getRoot()->getDesktopRepartition() + $value;
                $rest = 100 - $value;
                $constraint->message =  "desktopRepartition must be equal to " . $rest;
                break;
            case 'desktopRepartition':
                $totalRatio = $this->context->getRoot()->getMobileRepartition() + $value;
                $rest = 100 - $value;
                $constraint->message =  "mobileRepartition must be equal to " . $rest;
                break;

            default:
                # code...
                break;
        }

        if ($totalRatio !== 100) {

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{string}}', $value)

                ->addViolation();
        }
    }
}
