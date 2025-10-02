<?php


namespace App\Helper;

use App\Entity\Account;
use App\Entity\AccountRoleRight;
use App\Entity\Folder;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserAccountRole;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;

final class RightsHelper
{
  private $em;

  public function __construct(EntityManagerInterface $em)
  {
    $this->em = $em;
  }


  public function verifyUserInAccount($user, $account)
  {
    $userInAccount = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
      return $userAccountRole->getAccount() === $account;
    });

    if (!$userInAccount->count() > 0) {
      throw new Exception("account not found", Response::HTTP_FORBIDDEN);

      return false;
    }
  }

  /**
   * verify dans un account si l'utilisateur a l e droit du role au quel il es associer
   *
   * @param User $user
   * @param Account $account
   * @param string $right correspon au droit associer dans la table AccountRoleRight
   * @return boolean
   */
  public function verifyInAccountUserRight(User $user, Account $account, string $right): bool
  {
    /**
     * @var \App\Repository\UserAccountRoleRepository $userRoleRepository
     */
    $userRoleRepository = $this->em->getRepository(UserAccountRole::class);

    $acc = $userRoleRepository->findOneBy(["account" => $account, "user" => $user]);

    /**
     * @var \App\Repository\AccountRoleRightRepository $accountRoleRightRepository
     */
    $accountRoleRightRepository = $this->em->getRepository(AccountRoleRight::class);

    $userRoleAccount = $accountRoleRightRepository->findBy(['account' => $account, "role" => $acc->getRole()]);

    foreach ($userRoleAccount as  $accountRole) {

      if ($accountRole->getRights()->getCode() === $right) {
        return true;
      }
    }
    return false;
  }

  /**
   * verify si l'utilisateur du compte peut voir les repertoir grce au compte admin /editeur
   *
   * @param User $user
   * @param Account $account
   * @return void
   */
  public function verifyUserAccountRole(User $user, Account $account, $roles = []): bool
  {

    $accountRoles = $roles != [] ? $roles : [User::USER_ACCOUNT_ADMIN_ROLE, User::USER_ACCOUNT_EDITOR_ROLE];

    $userInAccountHasRole = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account, $accountRoles) {
      return $userAccountRole->getAccount() == $account && (in_array($userAccountRole->getRole()->getCode(), $accountRoles));
    });

    if (!$userInAccountHasRole->count() > 0) {
      return false;
    }
    return true;
  }
  /**
   * verify if at any level of folder an user can do action
   *
   * @param User $user
   * @param Folder $folder
   * @param String $permission doit definit en BDD $right->code()
   * @return boolean
   */
  public function canManageFolder(User $user, Folder $folder, $permission)
  {
    $hasAccountRight = $this->verifyInAccountUserRight($user, $folder->getAccount(), $permission);
    if ($hasAccountRight) {
      return true;
    }
    $account = $folder->getAccount();
    switch ($folder->getLevel()) {
      case 0:
      case 1:
      case 2:
        $userFolderRole = $folder->getUserFolderRoles()->filter(function ($userFolderRole) use ($user) {
          return $userFolderRole->getUser() == $user;
        });


        if (!$userFolderRole->count() > 0) {
          if ($folder->getLevel() === 0) {
            break;
          }
          $parentFolder = $folder->getParentFolder();
          return $this->canManageFolder($user, $parentFolder, $permission);
          break;
        }

        //verify si le droit de l'account permet a l utilisateur d 'inviter sur le dossier
        $accountRoleRightRepo = $this->em->getRepository(AccountRoleRight::class);

        /**
         * @var \App\Entity\AccountRoleRight $accountRoleRight
         */

        $folderRole = $userFolderRole->first()->getRole();
        $accountRoleRights = $accountRoleRightRepo->findBy([
          'role' => $folderRole,
          'account' => $account,
        ]);

        foreach ($accountRoleRights as $accountRoleRight) {
          if ($accountRoleRight->getRights()->getCode() == $permission) {
            return true;
            break;
          }
        }
        break;
    }
    return false;
  }


  /**
   * return string| null
   */
  public function findUserFolderRole(User $user, Folder $folder)
  {

    $userFolderRole = $folder->getUserFolderRoles()->filter(function ($userFolderRole) use ($user, $folder) {
      return $userFolderRole->getUser() == $user;
    });
    if ($userFolderRole->count() == 0) {
      return null;
    }
    return $userFolderRole->first()->getRole()->getCode();
  }

  public function FindUserAccountRole(User $user, Account $account)
  {

    $userInAccountRole = $user->getUserAccountRole()->filter(function ($userAccountRole) use ($account) {
      return $userAccountRole->getAccount() == $account;
    });

    if (!$userInAccountRole->count() > 0) {
      return null;
    }

    return $userInAccountRole->first()->getRole()->getCode();
  }
  /**
   * find if any parent folder has already been shared  verify if role is editor or reader or null
   * only verify account role for editor
   *
   * @param User $user
   * @param Folder $folder
   * @return null|string
   */
  public function findUserFolderRoleHeritage(User $user, Folder $folder)
  {

    if (in_array($this->FindUserAccountRole($user, $folder->getAccount()), [Role::ROLE_ADMIN, Role::ROLE_EDITOR])) {

      return Role::ROLE_EDITOR;
    }

    $userInFolderRole = $folder->getUserFolderRoles()->filter(function ($userFolderRole) use ($user) {
      return $userFolderRole->getUser() == $user;
    });
    /**
     * si le folder n'as pas d' utilisateur avec un role  on verify son parent
     */
    if (!$userInFolderRole->count() > 0) {

      if ($folder->getLevel() != 0) {

        $parentFolder = $folder->getParentFolder();
        return $this->findUserFolderRoleHeritage($user, $parentFolder);
      }

      return null;
    }

    return $this->findUserFolderRole($user, $folder);
  }

  public function findUserFromUserAccountRole($user, $account)
  {
    $userAccountRole = $account->getUserFolderRoles()->filter(function ($userAccountRole) use ($user, $account) {
      return $userAccountRole->getUser() == $user;
    });
    if ($userAccountRole->count() == 0) {
      return null;
    }
    return $userAccountRole->first()->getUser();
  }
}
