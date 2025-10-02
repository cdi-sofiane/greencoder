<?php

namespace App\Validation\Constraint\Folder;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FolderValidator extends ConstraintValidator
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

        /* @var App\Validator\Folder $constraint */

        if (null === $value || '' === $value) {
            return;
        }


        $parent = $this->context->getRoot()->getParentFolder() == null ? null : $this->context->getRoot()->getParentFolder();
        $currentLevel = $parent == null ? 0 : $parent->getLevel() + 1;
        /**
         * @var \App\Entity\Folder $entity
         */
        $entity = $this->em->getRepository(
            get_class($this->context->getRoot())
        )->findOneBy([
            "account" => $this->context->getRoot()->getAccount(),
            'parentFolder' =>  $parent,
            'level' => $currentLevel,
            'isInTrash' => false,
            'name' => $this->context->getRoot()->getName()
        ]);

        if ($entity != null) {

            $counter = 1; // Commencez à partir de 1 pour rechercher "folder (1)"
            $uniqueFolderName = $entity->getName() . ' (' . $counter . ')';

            // Vérifiez s'il existe déjà un dossier avec le même nom
            while ($this->em->getRepository(
                get_class($this->context->getRoot())
            )->findOneBy([
                "account" => $this->context->getRoot()->getAccount(),
                'parentFolder' =>  $parent,
                'level' => $currentLevel,
                'isInTrash' => false,
                'name' => $uniqueFolderName
            ])) {
                $counter++;
                $uniqueFolderName = $entity->getName() . ' (' . $counter . ')';
            }
            $this->context->getRoot()->setName($uniqueFolderName);
        }
    }
}
