<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Helper\RightsHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const USER_EDIT = 'USER_EDIT';
    public const USER_VIEW = 'USER_VIEW';
    private $rightsHelper;
    public function __construct(RightsHelper $rightsHelper)
    {
        $this->rightsHelper = $rightsHelper;
    }
    protected function supports($attribute, $subject): bool
    {

        if (!in_array($attribute, [self::USER_EDIT, self::USER_VIEW])) {
            throw new Exception("code not found", Response::HTTP_NOT_FOUND);
            return false;
        }

        if (!$subject instanceof User) {
            throw new Exception("not found", Response::HTTP_NOT_FOUND);
            return false;
        }

  
        return true;
    }

    protected function voteOnAttribute($attribute, $targetuser, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }
        /**
         * Authorise les ADMINISTRAEUR VIDMIZER
         */
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {
            return true;
        }

        /**
         * @var User $user
         * verifie si l'utilisateur existe sur l'account cible
         */
        $userAccountWith =  $user->getUserAccountRole()->filter(function ($userAccountRole) use ($targetuser) {

            return   $userAccountRole->getUser() == $targetuser;
        });

        if (!$userAccountWith->count() > 0) {
            throw new Exception("Forbidden", Response::HTTP_FORBIDDEN);
            return false;
        }
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::USER_EDIT:
                return $this->canEditUser($user, $userAccountWith->first());
                break;
            case self::USER_VIEW:
                // logic to determine if the user can VIEW
                // return true or false
                break;
        }

        return false;
    }

    public function canEditUser($user, UserAccountRole $userAccountRole): bool
    {
        $isGranted = $this->verifyAccountUserPermission($user, $userAccountRole->getAccount());


        return $isGranted;
    }

    private function verifyAccountUserPermission($user, $account): bool
    {
        $isGranted = $this->rightsHelper->verifyInAccountUserRight($user, $account, 'profile');

        return $isGranted;
    }
}
