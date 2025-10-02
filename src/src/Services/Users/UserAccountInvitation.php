<?php

namespace App\Services\Users;

use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Form\AccountType;
use App\Form\UseInvitationType;
use App\Form\UserAccountType;
use App\Repository\UserRepository;
use App\Services\AuthorizationService;
use App\Services\JsonResponseMessage;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserAccountInvitation
{
    private $userRepository;
    private $formFactory;
    private $passwordEncoder;
    public function __construct(
        userRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        FormFactoryInterface $formFactory
    ) {
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
        $this->passwordEncoder = $passwordEncoder;
    }
    /**
     * creer un nouvel utilisateur dans l application
     *
     */
    public function inviteCollaboratorToAccount(User $newUser)
    {

        $data = [
            'email' => $newUser->getEmail(),
            'password' => random_bytes(10),

        ];

        $form = $this->formFactory->create(UseInvitationType::class, $newUser);

        $formData = $form->submit($data);

        $formData->getData()->setCreatedAt(new DateTimeImmutable('now'));
        $formData->getData()->setPassword($data['password']);
        $formData->getData()->setUpdatedAt(new DateTimeImmutable('now'));


        return $formData->getData();
    }
}
