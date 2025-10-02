<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\User;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RoleAccountVoter extends Voter
{
    public const EDIT = User::USER_ACCOUNT_EDITOR_ROLE;
    public const ADMIN = User::USER_ACCOUNT_ADMIN_ROLE;
    public const VIEW = User::USER_ACCOUNT_USER_ROLE;

    protected function supports($attribute, $account): bool
    {

        if (!in_array($attribute, [self::EDIT, self::VIEW])) {
            return false;
        }

        if (!$account instanceof Account) {
            return false;
        }
        return true;
    }

    protected function voteOnAttribute($attribute, $account, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }
        if (array_intersect($user->getRoles(), User::ACCOUNT_ADMIN_ROLES)) {

            return true;
        }


        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::ADMIN:
            case self::EDIT:
                return $this->CanEditAccount($user, $account);
                break;
            case self::VIEW:
                // logic to determine if the user can VIEW
                // return true or false
                break;
        }

        return false;
    }
    private function CanEditAccount(User $loggedUser, $account)
    {
        $userInAccount = $loggedUser->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {

            return $userAccountRole->getAccount() === $account && (in_array($userAccountRole->getRole()->getCode(), [self::EDIT, self::ADMIN]));
        });
        if ($userInAccount->count() <= 0) {

            throw new Exception("not enought permission ", Response::HTTP_FORBIDDEN);
            return false;
        }
        return true;
    }
}
