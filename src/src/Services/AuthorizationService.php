<?php

namespace App\Services;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizationService
{
    const AS_DEV = "ROLE_DEV";
    const AS_VIDMIZER = "ROLE_VIDMIZER";
    const AS_USER = "ROLE_USER";
    private $request;
    private $userRepository;

    public function __construct(
        RequestStack $requestStack,
        UserRepository $userRepository
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->userRepository = $userRepository;
    }

    /**
     * verify if logged user can do isGrandedAction on target
     *
     * @param UserInterface $user
     * @return bool|void
     */

    public function check_access(UserInterface $user)
    {

        /**
         * if role is USER find if
         */
        switch ($user->getRoles()[0]) {
            case self::AS_USER:
                $user_uuid = '';
                if ($this->request->query->has('user_uuid') == true) {
                    $user_uuid = $this->request->query->get('user_uuid');
                } elseif ($this->request->request->has('user_uuid') == true) {
                    $user_uuid = $this->request->request->get('user_uuid');
                } elseif ($this->request->attributes->has('user_uuid') == true) {
                    $user_uuid = $this->request->attributes->get('user_uuid');
                }

                /**@var User $user */
                if ((($user_uuid === $user->getUuid()) || ($user_uuid === '')) && $user->getIsConditionAgreed() === true) {
                    return true;
                }
                return false;

            case self::AS_DEV:
            case self::AS_VIDMIZER:
                return true;
        }
    }

    /**
     * @return $user or null
     */
    public function getTargetUserOrNull($user)
    {
        $user_uuid = null;
        if ($this->request->query->has('user_uuid') == true) {
            $user_uuid = $this->request->query->get('user_uuid');
        } elseif ($this->request->request->has('user_uuid') == true) {
            $user_uuid = $this->request->request->get('user_uuid');
        } elseif ($this->request->attributes->has('user_uuid') == true) {
            $user_uuid = $this->request->attributes->get('user_uuid');
        }


        if (in_array($user->getRoles()[0], User::ACCOUNT_ADMIN_ROLES)) {
            $targetUser = $this->userRepository->findOneBy(['uuid' => $user_uuid]);

            if ($targetUser == null) {
                return null;
            }
            return $targetUser;
        }
        if (array_intersect($user->getRoles(), User::ACCOUNT_ROLES)) {

            if ((($user_uuid === $user->getUuid()) || ($user_uuid === null) || ($user_uuid === ''))  && $user->getIsConditionAgreed() === true) {
                return $user;
            }
            $targetUser = $this->userRepository->findOneBy(['uuid' => $user_uuid]);

            $userAccountRole = $targetUser->getUserAccountRole()->filter(function ($userAccountRole) use ($user) {

                return $userAccountRole->getUser() == $user;
            });

            if ($userAccountRole->count() > 0) {
                return $userAccountRole->first()->getUser();
            }

            return null;
        }
    }
}
